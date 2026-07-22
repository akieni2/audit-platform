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
        foreach ($xpath->query('//w:body/w:tbl') as $table) {
            $rows = [];
            foreach ($xpath->query('./w:tr', $table) as $row) {
                $cells = [];
                foreach ($xpath->query('./w:tc', $row) as $cell) {
                    $texts = [];
                    foreach ($xpath->query('.//w:t', $cell) as $text) {
                        $texts[] = $text->textContent;
                    }
                    $cells[] = trim(implode(' ', $texts));
                }
                $rows[] = $cells;
            }
            $tables[] = $rows;
        }

        $metadata = $this->keyValueTable($tables[0] ?? []);
        $context = $tables[1] ?? [];
        $questions = [];
        foreach (array_slice($tables, 2) as $index => $rows) {
            if (count($rows) < 2) {
                continue;
            }
            $header = $rows[0];
            $question = $rows[1];
            $observation = trim((string) ($rows[3][0] ?? ''));
            $questions[] = [
                'sequence' => $index + 1,
                'theme' => trim((string) ($header[0] ?? '')),
                'question' => trim((string) ($question[0] ?? '')),
                'answer' => $this->normalizeAnswer((string) ($question[1] ?? '')),
                'expected_documents' => trim((string) ($question[2] ?? '')),
                'stakeholders' => trim((string) ($question[3] ?? '')),
                'observation' => $observation,
            ];
        }

        return [
            'metadata' => $metadata,
            'context' => $context,
            'questions' => $questions,
            'question_count' => count($questions),
        ];
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
