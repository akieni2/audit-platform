import fs from 'node:fs';
import path from 'node:path';

const inputPath = path.resolve(process.argv[2] || 'storage/journal-modifications-session.md');
const outputPath = path.resolve(process.argv[3] || 'storage/journal-modifications-session.pdf');

if (!fs.existsSync(inputPath)) {
  console.error(`Input not found: ${inputPath}`);
  process.exit(1);
}

const raw = fs.readFileSync(inputPath, 'utf8').replace(/\r\n/g, '\n');

function normalizeText(text) {
  return text.replace(/\t/g, '    ');
}

function wrapText(text, maxChars) {
  if (text.length <= maxChars) return [text];
  const words = text.split(/\s+/);
  const lines = [];
  let current = '';
  for (const word of words) {
    const next = current ? `${current} ${word}` : word;
    if (next.length <= maxChars) {
      current = next;
    } else {
      if (current) lines.push(current);
      if (word.length <= maxChars) {
        current = word;
      } else {
        let remaining = word;
        while (remaining.length > maxChars) {
          lines.push(remaining.slice(0, maxChars - 1) + '-');
          remaining = remaining.slice(maxChars - 1);
        }
        current = remaining;
      }
    }
  }
  if (current) lines.push(current);
  return lines;
}

function toLineSpecs(markdown) {
  const specs = [];
  for (const rawLine of markdown.split('\n')) {
    const line = normalizeText(rawLine);

    if (line.trim() === '') {
      specs.push({ text: '', size: 8, bold: false, spacer: true });
      continue;
    }

    if (line.startsWith('# ')) {
      specs.push({ text: line.slice(2).trim(), size: 18, bold: true });
      specs.push({ text: '', size: 6, bold: false, spacer: true });
      continue;
    }

    if (line.startsWith('## ')) {
      specs.push({ text: line.slice(3).trim(), size: 14, bold: true });
      continue;
    }

    if (line.startsWith('### ')) {
      specs.push({ text: line.slice(4).trim(), size: 12, bold: true });
      continue;
    }

    if (line.startsWith('- ')) {
      const wrapped = wrapText(`• ${line.slice(2).trim()}`, 92);
      for (const part of wrapped) {
        specs.push({ text: part, size: 10, bold: false });
      }
      continue;
    }

    const wrapped = wrapText(line, 98);
    for (const part of wrapped) {
      specs.push({ text: part, size: 10, bold: false });
    }
  }
  return specs;
}

function escapePdfText(text) {
  return text
    .replace(/\\/g, '\\\\')
    .replace(/\(/g, '\\(')
    .replace(/\)/g, '\\)')
    .replace(/\r/g, '')
    .replace(/\n/g, ' ');
}

function toBinaryString(str) {
  let out = '';
  for (const ch of str) {
    const code = ch.charCodeAt(0);
    out += String.fromCharCode(code <= 255 ? code : 63);
  }
  return out;
}

const pageWidth = 595;
const pageHeight = 842;
const marginX = 48;
const marginTop = 50;
const marginBottom = 42;

const lineSpecs = toLineSpecs(raw);

const pages = [];
let currentPage = [];
let y = pageHeight - marginTop;

for (const spec of lineSpecs) {
  const lineHeight = spec.spacer ? 8 : Math.ceil(spec.size * 1.45);
  if (y - lineHeight < marginBottom) {
    pages.push(currentPage);
    currentPage = [];
    y = pageHeight - marginTop;
  }
  currentPage.push({ ...spec, x: marginX, y });
  y -= lineHeight;
}
if (currentPage.length) pages.push(currentPage);

const objects = [];

function addObject(content) {
  objects.push(content);
  return objects.length;
}

const catalogNum = addObject('<< /Type /Catalog /Pages 2 0 R >>');
const pagesNum = addObject(''); // placeholder
const fontRegularNum = addObject('<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>');
const fontBoldNum = addObject('<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>');

const pageRefs = [];

for (const page of pages) {
  const streamLines = ['BT'];
  for (const item of page) {
    if (item.spacer) continue;
    const fontRef = item.bold ? '/F2' : '/F1';
    const text = toBinaryString(escapePdfText(item.text));
    streamLines.push(`${fontRef} ${item.size} Tf`);
    streamLines.push(`1 0 0 1 ${item.x} ${item.y} Tm`);
    streamLines.push(`(${text}) Tj`);
  }
  streamLines.push('ET');
  const stream = streamLines.join('\n');
  const streamBinary = Buffer.from(stream, 'binary');
  const contentNum = addObject(`<< /Length ${streamBinary.length} >>\nstream\n${stream}\nendstream`);
  const pageNum = addObject(
    `<< /Type /Page /Parent ${pagesNum} 0 R /MediaBox [0 0 ${pageWidth} ${pageHeight}] /Resources << /Font << /F1 ${fontRegularNum} 0 R /F2 ${fontBoldNum} 0 R >> >> /Contents ${contentNum} 0 R >>`
  );
  pageRefs.push(`${pageNum} 0 R`);
}

objects[pagesNum - 1] = `<< /Type /Pages /Kids [${pageRefs.join(' ')}] /Count ${pageRefs.length} >>`;

let pdf = '%PDF-1.4\n%\xE2\xE3\xCF\xD3\n';
const offsets = [0];

for (let i = 0; i < objects.length; i++) {
  offsets.push(Buffer.byteLength(pdf, 'binary'));
  pdf += `${i + 1} 0 obj\n${objects[i]}\nendobj\n`;
}

const xrefOffset = Buffer.byteLength(pdf, 'binary');
pdf += `xref\n0 ${objects.length + 1}\n`;
pdf += '0000000000 65535 f \n';
for (let i = 1; i < offsets.length; i++) {
  pdf += `${String(offsets[i]).padStart(10, '0')} 00000 n \n`;
}
pdf += `trailer\n<< /Size ${objects.length + 1} /Root ${catalogNum} 0 R >>\nstartxref\n${xrefOffset}\n%%EOF\n`;

fs.mkdirSync(path.dirname(outputPath), { recursive: true });
fs.writeFileSync(outputPath, Buffer.from(pdf, 'binary'));
console.log(`PDF generated: ${outputPath}`);

