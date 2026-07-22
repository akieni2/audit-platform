<?php

namespace Tests\Feature;

use App\Models\QuestionnaireSection;
use App\Models\User;
use App\Services\Dgcpt\QuestionnaireImportService;
use App\Services\Questionnaires\QuestionnairePublishingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class QuestionnaireWordLibraryImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_page_submits_a_real_multipart_form_and_publishes_the_questionnaire(): void
    {
        Storage::fake('local');
        $path = $this->makeQuestionnaireDocx();
        $file = new UploadedFile(
            $path,
            'Questionnaire_Gouvernance.docx',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            null,
            true,
        );

        try {
            $response = $this->actingAs(User::factory()->create())->post(route('dgcpt.questionnaire-import.store'), [
                'file' => $file,
                'name' => 'Gouvernance DSI',
                'publish_now' => '1',
            ]);

            $response->assertSessionHasNoErrors();
            $template = \App\Models\QuestionnaireTemplate::query()->where('name', 'Gouvernance DSI')->firstOrFail();
            $response->assertRedirect(route('questionnaire-builder.edit', $template));
            $this->assertTrue($template->active);
            $this->assertSame('published', $template->lifecycle_status);
        } finally {
            @unlink($path);
        }
    }

    public function test_word_questionnaire_becomes_a_reusable_published_hierarchical_template(): void
    {
        Storage::fake('local');
        $path = $this->makeQuestionnaireDocx();
        $file = new UploadedFile(
            $path,
            'Questionnaire_Alignement-Strat.docx',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            null,
            true,
        );
        $user = User::factory()->create();

        try {
            $template = app(QuestionnaireImportService::class)->importToQuestionnaireTemplate($file, [
                'name' => 'Alignement stratégique DSI',
                'created_by' => $user->id,
            ]);
            $template = app(QuestionnairePublishingService::class)->publish($template, $user);

            $this->assertTrue($template->active);
            $this->assertSame('published', $template->lifecycle_status);
            $this->assertNotNull($template->source_document_path);
            Storage::disk('local')->assertExists($template->source_document_path);
            $this->assertDatabaseHas('questionnaire_sections', [
                'questionnaire_template_id' => $template->id,
                'title' => 'ALIGNEMENT STRATEGIQUE',
                'section_type' => QuestionnaireSection::TYPE_THEME,
                'parent_section_id' => null,
            ]);
            $theme = $template->sections()->where('section_type', QuestionnaireSection::TYPE_THEME)->firstOrFail();
            $thematic = $template->sections()->where('section_type', QuestionnaireSection::TYPE_THEMATIC)->firstOrFail();
            $subtheme = $template->sections()->where('section_type', QuestionnaireSection::TYPE_SUBTHEME)->firstOrFail();
            $this->assertSame($theme->id, $thematic->parent_section_id);
            $this->assertSame($thematic->id, $subtheme->parent_section_id);
            $this->assertDatabaseHas('questionnaire_questions', [
                'questionnaire_section_id' => $subtheme->id,
                'expected_documents' => 'SDSI, plan stratégique',
                'active' => true,
            ]);

            $this->actingAs($user)
                ->post(route('questionnaire-templates.duplicate', $template))
                ->assertRedirect();
            $clone = $template->derivedVersions()->where('lifecycle_status', 'draft')->latest('id')->firstOrFail();
            $cloneTheme = $clone->sections()->where('section_type', QuestionnaireSection::TYPE_THEME)->firstOrFail();
            $cloneThematic = $clone->sections()->where('section_type', QuestionnaireSection::TYPE_THEMATIC)->firstOrFail();
            $cloneSubtheme = $clone->sections()->where('section_type', QuestionnaireSection::TYPE_SUBTHEME)->firstOrFail();
            $this->assertSame($cloneTheme->id, $cloneThematic->parent_section_id);
            $this->assertSame($cloneThematic->id, $cloneSubtheme->parent_section_id);
            $this->assertSame(1, $cloneSubtheme->questions()->count());
        } finally {
            @unlink($path);
        }
    }

    private function makeQuestionnaireDocx(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'library_questionnaire_').'.docx';
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>');
        $zip->addFromString('word/document.xml', $this->documentXml());
        $zip->close();

        return $path;
    }

    private function documentXml(): string
    {
        $paragraph = static fn (string $text): string => '<w:p><w:r><w:t>'.htmlspecialchars($text, ENT_XML1).'</w:t></w:r></w:p>';
        $table = static fn (array $rows): string => '<w:tbl>'.implode('', array_map(
            static fn (array $cells): string => '<w:tr>'.implode('', array_map(
                static fn (string $text): string => '<w:tc><w:p><w:r><w:t>'.htmlspecialchars($text, ENT_XML1).'</w:t></w:r></w:p></w:tc>',
                $cells,
            )).'</w:tr>',
            $rows,
        )).'</w:tbl>';

        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>'
            .$table([['Version', 'V3']]).$table([['Direction', 'DSI']])
            .$paragraph('4. ALIGNEMENT STRATEGIQUE')
            .$paragraph('Thématique 1 : Alignement SDSI')
            .$paragraph('Sous-Thématique : Vision stratégique')
            .$table([
                ['Vision stratégique', 'Réponse (O/N/NA)', 'Documents attendus', 'Interlocuteurs'],
                ['Le SDSI est-il aligné avec la stratégie ?', '', 'SDSI, plan stratégique', 'DSI'],
                ['Observations', 'Observations', 'Observations', 'Observations'],
                ['', '', '', ''],
            ])
            .'</w:body></w:document>';
    }
}
