/**
 * Convert Markdown guides to print-ready HTML (for Edge headless PDF).
 * Usage: node docs/tools/md-to-html.mjs [input.md ...]
 */
import { readFileSync, writeFileSync, mkdirSync, existsSync } from 'node:fs';
import { dirname, basename, resolve, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const root = resolve(__dirname, '..', '..');
const outDir = resolve(root, 'docs', 'pdf', '_html');
const cssPath = resolve(__dirname, 'guide-pdf.css');

const { marked } = await import('./vendor/marked.esm.js');

marked.setOptions({
    gfm: true,
    breaks: false,
});

function extractTitle(markdown, fallback) {
    const match = markdown.match(/^#\s+(.+)$/m);
    return match ? match[1].trim() : fallback;
}

function wrapHtml(title, bodyHtml) {
    const css = readFileSync(cssPath, 'utf8');
    const generated = new Date().toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });

    return `<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>${escapeHtml(title)}</title>
  <style>${css}</style>
</head>
<body>
  <header class="doc-header">
    <p class="org">Direction générale du Contrôle des Marchés publics et des Engagements financiers</p>
    <h1>${escapeHtml(title)}</h1>
    <p class="doc-meta">Plateforme d'audit — Documentation · Généré le ${generated}</p>
  </header>
  <main class="doc-content">
    ${bodyHtml}
  </main>
  <footer class="doc-footer">
    Document interne — Plateforme d'audit DGCPT · Ne pas diffuser sans autorisation
  </footer>
</body>
</html>`;
}

function escapeHtml(text) {
    return text
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function stripLeadingH1(markdown) {
    return markdown.replace(/^#\s+.+(\r?\n)/, '');
}

function convertFile(inputPath) {
    const abs = resolve(inputPath);
    const raw = readFileSync(abs, 'utf8');
    const base = basename(abs, '.md');
    const title = extractTitle(raw, base);
    const markdown = stripLeadingH1(raw);
    const bodyHtml = marked.parse(markdown);
    const html = wrapHtml(title, bodyHtml);
    const outPath = join(outDir, `${base}.html`);

    writeFileSync(outPath, html, 'utf8');
    return outPath;
}

mkdirSync(outDir, { recursive: true });

const inputs = process.argv.slice(2);
const defaultGuides = [
    'docs/guides/01-guide-utilisateur.md',
    'docs/guides/02-guide-administrateur.md',
    'docs/guides/03-guide-workflows.md',
    'docs/guides/04-guide-ia-copilot.md',
    'docs/guides/05-manuel-procedures-securite.md',
    'docs/guides/06-guide-exploitation.md',
    'docs/README.md',
].map((p) => resolve(root, p));

const files = inputs.length > 0 ? inputs.map((p) => resolve(p)) : defaultGuides;

for (const file of files) {
    if (!existsSync(file)) {
        console.error(`SKIP (missing): ${file}`);
        continue;
    }
    const out = convertFile(file);
    console.log(out);
}
