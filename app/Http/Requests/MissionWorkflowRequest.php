<?php

namespace App\Http\Requests;

use App\Services\Missions\MissionWorkflowService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MissionWorkflowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'action' => [
                'required',
                'string',
                Rule::in([
                    MissionWorkflowService::ACTION_DEMARRER,
                    MissionWorkflowService::ACTION_CLOTURER,
                    MissionWorkflowService::ACTION_VALIDER_IS,
                    MissionWorkflowService::ACTION_DEMANDER_CORRECTIONS,
                    MissionWorkflowService::ACTION_VALIDER_COPRI,
                    MissionWorkflowService::ACTION_RENVOYER_COPRI,
                ]),
            ],
            'comment' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $action = $this->string('action')->toString();
            $comment = trim((string) $this->input('comment'));

            if (in_array($action, [
                MissionWorkflowService::ACTION_DEMANDER_CORRECTIONS,
                MissionWorkflowService::ACTION_RENVOYER_COPRI,
            ], true) && $comment === '') {
                $validator->errors()->add('comment', 'Un commentaire est obligatoire pour cette décision.');
            }
        });
    }
}
