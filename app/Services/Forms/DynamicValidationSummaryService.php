<?php

namespace App\Services\Forms;

use Illuminate\Support\ViewErrorBag;

class DynamicValidationSummaryService
{
    /**
     * @param  array<string, mixed>  $form
     * @return array<string, mixed>
     */
    public function summarize(array $form, ViewErrorBag $errors): array
    {
        $defaultBag = $errors->getBag('default');
        $visibleFields = collect($form['visible_fields'] ?? [])->keyBy('field_key');
        $messages = [];

        foreach ($defaultBag->messages() as $fieldKey => $fieldMessages) {
            $label = $visibleFields->get($fieldKey)['label'] ?? $fieldKey;

            foreach ($fieldMessages as $message) {
                $messages[] = [
                    'field' => $fieldKey,
                    'label' => $label,
                    'message' => $message,
                ];
            }
        }

        return [
            'count' => count($messages),
            'messages' => $messages,
        ];
    }
}
