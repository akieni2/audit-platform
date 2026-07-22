<?php

namespace App\Services\Questionnaires;

use DOMDocument;
use DOMXPath;
use InvalidArgumentException;
use ZipArchive;

class WordQuestionnaireExtractionService
{
    /** @return array<string, mixed> */
    public function extract(string $path): array
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw new InvalidArgumentException('Le document Word ne peut pas être ouvert. Convertissez les anciens fichiers .doc au format .docx.');
        }
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        if ($xml === false) {
            throw new InvalidArgumentException('Le document ne contient pas de contenu Word exploitable.');
        }

        $dom = new DOMDocument;
        $dom->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS);
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        $tables = [];
        $questions = [];
        $theme = $thematic = $subtheme = null;
        foreach ($xpath->query('//w:body/*') as $element) {
            if ($element->localName === 'p') {
                $paragraph = $this->nodeText($xpath, $element);
                if (preg_match('/^\s*\d+\.\s*(.+)$/u', $paragraph, $matches)) {
                    $theme = $this->cleanHeading($matches[1]);
                    $thematic = $subtheme = null;
                } elseif (preg_match('/^\s*Th.matique\s*\d+\s*:\s*(.+)$/iu', $paragraph, $matches)) {
                    $thematic = $this->cleanHeading($matches[1]);
                    $subtheme = null;
                } elseif (preg_match('/^\s*Sous\s*-\s*Th.matique\s*:\s*(.+)$/iu', $paragraph, $matches)) {
                    $subtheme = $this->cleanHeading($matches[1]);
                }

                continue;
            }

            if ($element->localName !== 'tbl') {
                continue;
            }

            $rows = $this->tableRows($xpath, $element);
            $tables[] = $rows;
            if ($theme === null || count($rows) < 2) {
                continue;
            }

            $header = $rows[0];
            $question = $rows[1];
            $questions[] = [
                'sequence' => count($questions) + 1,
                'theme' => $theme,
                'thematic' => $thematic ?: 'Thématique importée',
                'subtheme' => $subtheme ?: trim((string) ($header[0] ?? 'Questions importées')),
                'question' => trim((string) ($question[0] ?? '')),
                'answer' => $this->normalizeAnswer((string) ($question[1] ?? '')),
                'expected_documents' => trim((string) ($question[2] ?? '')),
                'stakeholders' => trim((string) ($question[3] ?? '')),
                'observation' => trim((string) ($rows[3][0] ?? '')),
            ];
        }

        $metadata = $this->keyValueTable($tables[0] ?? []);
        $context = $tables[1] ?? [];
        if ($questions === []) {
            foreach (array_slice($tables, 2) as $rows) {
                if (count($rows) < 2) {
                    continue;
                }
                $header = $rows[0];
                $question = $rows[1];
                $questions[] = [
                    'sequence' => count($questions) + 1,
                    'theme' => 'Questionnaire importé',
                    'thematic' => 'Questions importées',
                    'subtheme' => trim((string) ($header[0] ?? 'Questions importées')),
                    'question' => trim((string) ($question[0] ?? '')),
                    'answer' => $this->normalizeAnswer((string) ($question[1] ?? '')),
                    'expected_documents' => trim((string) ($question[2] ?? '')),
                    'stakeholders' => trim((string) ($question[3] ?? '')),
                    'observation' => trim((string) ($rows[3][0] ?? '')),
                ];
            }
        }

        return [
            'metadata' => $metadata,
            'context' => $context,
            'questions' => $questions,
            'question_count' => count($questions),
        ];
    }

    private function nodeText(DOMXPath $xpath, \DOMNode $node): string
    {
        $texts = [];
        foreach ($xpath->query('.//w:t', $node) as $text) {
            $texts[] = $text->textContent;
        }

        return trim(implode('', $texts));
    }

    /** @return list<list<string>> */
    private function tableRows(DOMXPath $xpath, \DOMNode $table): array
    {
        $rows = [];
        foreach ($xpath->query('./w:tr', $table) as $row) {
            $cells = [];
            foreach ($xpath->query('./w:tc', $row) as $cell) {
                $cells[] = $this->nodeText($xpath, $cell);
            }
            $rows[] = $cells;
        }

        return $rows;
    }

    private function cleanHeading(string $heading): string
    {
        return trim(preg_replace('/\s+/u', ' ', $heading) ?: $heading);
    }

    /** @param array<string, mixed> $extracted @return array<string, mixed> */
    public function suggest(array $extracted): array
    {
        $risks = [];
        $strengths = [];
        $weaknesses = [];
        $raci = [];
        foreach ($extracted['questions'] ?? [] as $question) {
            $answer = mb_strtolower((string) ($question['answer'] ?? ''));
            $observation = trim((string) ($question['observation'] ?? ''));
            $negativeObservation = $observation !== '' && preg_match('/\b(absence|aucun|manque|obsol[eè]te|inadapt[eé]e?|pas de|non document|insuffisant|critique)\b/iu', $observation);

            if ($answer === 'non' || $negativeObservation) {
                $risks[] = [
                    'theme' => $question['theme'],
                    'source_question' => $question['question'],
                    'observation' => $observation,
                    'proposed_level' => $answer === 'non' ? 'élevé' : 'moyen',
                    'requires_human_validation' => true,
                ];
                $weaknesses[] = ['theme' => $question['theme'], 'observation' => $observation ?: $question['question']];
            } elseif ($answer === 'oui' && $observation !== '') {
                $strengths[] = ['theme' => $question['theme'], 'observation' => $observation];
            }

            if (! empty($question['stakeholders'])) {
                $raci[] = [
                    'activity' => $question['theme'],
                    'consulted' => $question['stakeholders'],
                    'source_question' => $question['question'],
                ];
            }
        }

        return [
            'risk_candidates' => $risks,
            'swot' => ['strengths' => $strengths, 'weaknesses' => $weaknesses, 'opportunities' => [], 'threats' => []],
            'raci_candidates' => $raci,
            'requires_human_validation' => true,
        ];
    }

    /** @param list<list<string>> $rows @return array<string, string> */
    private function keyValueTable(array $rows): array
    {
        $result = [];
        foreach ($rows as $row) {
            if (($row[0] ?? '') !== '') {
                $result[trim($row[0])] = trim((string) ($row[1] ?? ''));
            }
        }

        return $result;
    }

    private function normalizeAnswer(string $answer): string
    {
        $answer = mb_strtolower(trim(str_replace(['--', '–'], '', $answer)));

        return match (true) {
            str_starts_with($answer, 'oui'), $answer === 'o' => 'oui',
            str_starts_with($answer, 'non'), $answer === 'n' => 'non',
            in_array($answer, ['na', 'n/a', 'non applicable'], true) => 'na',
            default => $answer,
        };
    }
}
