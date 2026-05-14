<?php

namespace App\Services\Reporting;

use App\Models\MethodologyTemplate;
use App\Services\Methodologies\MethodologyWorkflowMappingService;

class MethodologyReportService
{
    public function __construct(
        private MethodologyWorkflowMappingService $mappings,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function report(MethodologyTemplate $template): array
    {
        return [
            'methodology' => $template,
            'coverage' => $this->mappings->coverage($template),
            'stack' => $this->mappings->resolveStack($template, $template->department_id),
            'formats' => ['pdf', 'word', 'excel'],
        ];
    }
}
