<?php

namespace App\Services\Ai;

use App\Domain\Ai\Enums\AiContextType;
use App\Models\AiPromptTemplate;
use Illuminate\Support\Facades\Schema;

class AiPromptEngineService
{
    public function resolveSystemPrompt(AiContextType $contextType, ?int $departmentId = null): string
    {
        if (Schema::hasTable('ai_prompt_templates')) {
            $template = AiPromptTemplate::query()
                ->where('context_type', $contextType->value)
                ->where('active', true)
                ->when($departmentId, fn ($q) => $q->where('department_id', $departmentId))
                ->first();

            if ($template !== null) {
                return $template->system_prompt;
            }
        }

        return 'Tu es un copilote d\'audit et de contrôle interne. '
            .'Tu fournis des suggestions assistives uniquement. '
            .'Tu ne valides jamais automatiquement une mission, un workflow ou un risque. '
            .'Tu indiques toujours qu\'une validation humaine est requise.';
    }

    public function renderUserPrompt(string $template, array $context): string
    {
        $rendered = $template;
        foreach ($context as $key => $value) {
            if (is_scalar($value)) {
                $rendered = str_replace('{{'.$key.'}}', (string) $value, $rendered);
            }
        }

        return $rendered;
    }
}
