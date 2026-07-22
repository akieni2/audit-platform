<?php

namespace Tests\Unit;

use App\Services\Questionnaires\WordQuestionnaireExtractionService;
use Tests\TestCase;
use ZipArchive;

class WordQuestionnaireExtractionServiceTest extends TestCase
{
    public function test_it_extracts_answers_and_flags_a_risk_even_when_yes_has_a_negative_observation(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'questionnaire_').'.docx';
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('word/document.xml', $this->documentXml());
        $zip->close();

        try {
            $service = app(WordQuestionnaireExtractionService::class);
            $extracted = $service->extract($path);
            $suggestions = $service->suggest($extracted);

            $this->assertSame(1, $extracted['question_count']);
            $this->assertSame('oui', $extracted['questions'][0]['answer']);
            $this->assertSame('Chef du service Réseau', $extracted['questions'][0]['stakeholders']);
            $this->assertCount(1, $suggestions['risk_candidates']);
            $this->assertCount(1, $suggestions['swot']['weaknesses']);
            $this->assertCount(1, $suggestions['raci_candidates']);
            $this->assertTrue($suggestions['requires_human_validation']);
        } finally {
            @unlink($path);
        }
    }

    private function documentXml(): string
    {
        $table = static fn (array $rows): string => '<w:tbl>'.implode('', array_map(
            static fn (array $cells): string => '<w:tr>'.implode('', array_map(
                static fn (string $text): string => '<w:tc><w:p><w:r><w:t>'.htmlspecialchars($text, ENT_XML1).'</w:t></w:r></w:p></w:tc>',
                $cells,
            )).'</w:tr>',
            $rows,
        )).'</w:tbl>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>'
            .$table([['Mission', 'Audit DSI']])
            .$table([['Contexte', 'DSI']])
            .$table([
                ['Documentation technique', 'Réponse', 'Documents attendus', 'Parties prenantes'],
                ['La documentation est-elle actualisée ?', 'Oui', 'Procédures réseau', 'Chef du service Réseau'],
                ['', '', '', ''],
                ['Documentation obsolète et inadaptée aux équipements actuels.'],
            ])
            .'</w:body></w:document>';
    }
}
