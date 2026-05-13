@php
    /** @var string $route */
    /** @var string|null $method */
    /** @var string $submitLabel */
    /** @var \App\Models\QuestionnaireQuestion|null $question */
    $question ??= null;
    $method = $method ?? 'POST';
    $metadata = old('metadata', $question?->metadata ?? [
        'options' => [],
        'scoring' => ['enabled' => false, 'weight' => 0],
        'risk_mapping' => ['enabled' => false, 'category' => null, 'default_criticality' => null],
        'documents' => ['required' => false, 'list' => []],
    ]);
    $metadataJson = json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
@endphp

<form method="POST" action="{{ $route }}" class="space-y-4">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="grid gap-4 lg:grid-cols-2">
        <div>
            <label class="dgcpt-label">Code</label>
            <input name="code" type="text" value="{{ old('code', $question?->code) }}" class="dgcpt-input font-mono text-sm" />
        </div>
        <div>
            <label class="dgcpt-label">Ordre</label>
            <input name="sort_order" type="number" min="0" value="{{ old('sort_order', $question?->sort_order ?? 0) }}" class="dgcpt-input" />
        </div>
        <div class="lg:col-span-2">
            <label class="dgcpt-label">Question</label>
            <textarea name="question" rows="2" required class="dgcpt-textarea">{{ old('question', $question?->question) }}</textarea>
        </div>
        <div class="lg:col-span-2">
            <label class="dgcpt-label">Aide / consigne</label>
            <textarea name="help_text" rows="2" class="dgcpt-textarea">{{ old('help_text', $question?->help_text) }}</textarea>
        </div>
        <div>
            <label class="dgcpt-label">Type</label>
            <select name="question_type" class="dgcpt-input" required>
                @foreach (\App\Models\QuestionnaireQuestion::questionTypeLabels() as $type => $label)
                    <option value="{{ $type }}" @selected(old('question_type', $question?->question_type ?? \App\Models\QuestionnaireQuestion::TYPE_TEXT) === $type)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="dgcpt-label">Criticité par défaut</label>
            <select name="risk_level" class="dgcpt-input">
                <option value="">— Aucune —</option>
                @foreach (\App\Domain\Risk\Enums\CriticalityLevel::options() as $value => $label)
                    <option value="{{ $value }}" @selected(old('risk_level', $question?->risk_level ?? data_get($metadata, 'risk_mapping.default_criticality')) === $value)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="dgcpt-label">Catégorie risque</label>
            <input name="risk_category" type="text" value="{{ old('risk_category', $question?->risk_category ?? data_get($metadata, 'risk_mapping.category')) }}" class="dgcpt-input" />
        </div>
        <div>
            <label class="dgcpt-label">Options</label>
            <textarea name="options_text" rows="3" class="dgcpt-textarea" placeholder="Une option par ligne">{{ old('options_text', collect(data_get($metadata, 'options', []))->implode("\n")) }}</textarea>
        </div>
        <div>
            <label class="dgcpt-label">Pièces attendues</label>
            <textarea name="documents_list_text" rows="3" class="dgcpt-textarea" placeholder="Une pièce par ligne">{{ old('documents_list_text', collect(data_get($metadata, 'documents.list', preg_split('/\r\n|\r|\n/', (string) ($question?->expected_documents ?? '')) ?: []))->filter()->implode("\n")) }}</textarea>
        </div>
        <div class="lg:col-span-2">
            <label class="dgcpt-label">Metadata JSON</label>
            <textarea name="metadata_json" rows="10" class="dgcpt-textarea font-mono text-xs">{{ old('metadata_json', $metadataJson) }}</textarea>
            <p class="mt-1 text-xs text-[#9FB3C8]">Le JSON est normalisé automatiquement vers la structure officielle du core.</p>
        </div>
    </div>

    <div class="grid gap-4 rounded-2xl border border-[rgba(0,209,255,0.12)] bg-[rgba(7,16,34,0.6)] p-4 md:grid-cols-2 xl:grid-cols-4">
        <label class="inline-flex items-center gap-2 text-sm text-[#E6EEF8]">
            <input type="checkbox" name="required" value="1" @checked(old('required', $question?->required ?? false)) />
            Obligatoire
        </label>
        <label class="inline-flex items-center gap-2 text-sm text-[#E6EEF8]">
            <input type="checkbox" name="allows_observation" value="1" @checked(old('allows_observation', $question?->allows_observation ?? true)) />
            Autorise observation
        </label>
        <label class="inline-flex items-center gap-2 text-sm text-[#E6EEF8]">
            <input type="checkbox" name="allows_risk_detection" value="1" @checked(old('allows_risk_detection', $question?->allows_risk_detection ?? false)) />
            Détection risque
        </label>
        <label class="inline-flex items-center gap-2 text-sm text-[#E6EEF8]">
            <input type="checkbox" name="active" value="1" @checked(old('active', $question?->active ?? true)) />
            Active
        </label>
        <label class="inline-flex items-center gap-2 text-sm text-[#E6EEF8]">
            <input type="checkbox" name="scoring_enabled" value="1" @checked(old('scoring_enabled', data_get($metadata, 'scoring.enabled', false))) />
            Scoring actif
        </label>
        <div>
            <label class="dgcpt-label">Poids scoring</label>
            <input name="scoring_weight" type="number" min="0" max="100" value="{{ old('scoring_weight', data_get($metadata, 'scoring.weight', 0)) }}" class="dgcpt-input" />
        </div>
        <label class="inline-flex items-center gap-2 text-sm text-[#E6EEF8]">
            <input type="checkbox" name="risk_mapping_enabled" value="1" @checked(old('risk_mapping_enabled', data_get($metadata, 'risk_mapping.enabled', false))) />
            Risk mapping
        </label>
        <label class="inline-flex items-center gap-2 text-sm text-[#E6EEF8]">
            <input type="checkbox" name="documents_required" value="1" @checked(old('documents_required', data_get($metadata, 'documents.required', false))) />
            Pièces obligatoires
        </label>
    </div>

    <button type="submit" class="dgcpt-btn-primary">{{ $submitLabel }}</button>
</form>
