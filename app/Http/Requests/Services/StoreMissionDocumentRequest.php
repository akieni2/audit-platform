<?php

namespace App\Http\Requests\Services;

use App\Models\Mission;
use App\Policies\MissionDocumentPolicy;
use Illuminate\Foundation\Http\FormRequest;

class StoreMissionDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $mission = $this->route('mission');

        return $mission instanceof Mission
            && $this->user() !== null
            && app(MissionDocumentPolicy::class)->create($this->user(), $mission);
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:20480', 'mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg,gif,webp,zip'],
            'category' => ['nullable', 'string', 'max:64'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
