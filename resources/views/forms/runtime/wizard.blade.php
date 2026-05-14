<div class="space-y-5" data-runtime-wizard>
    @include('forms.runtime.stepper', ['wizardData' => $wizardData])
    @include('forms.runtime.autosave-status', ['autosaveData' => $autosaveData])
    @include('forms.runtime.validation-summary', ['validationSummary' => $validationSummary])

    <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr),320px]">
        <form method="POST"
              action="{{ route('workflow-runtime.stage.submit', ['mission' => $instance->mission_id, 'stage' => $stage]) }}"
              class="space-y-5"
              enctype="multipart/form-data"
              data-autosave-form>
            @csrf

            @if ($entretien)
                <input type="hidden" name="entretien_id" value="{{ $entretien->id }}" />
            @endif

            @foreach ($wizardData['steps'] as $step)
                <section class="wizard-step rounded-[2rem] border border-[rgba(0,209,255,0.10)] bg-[rgba(5,8,22,0.72)] p-6"
                         data-step-index="{{ $step['index'] }}"
                         @if (! $loop->first) hidden @endif>
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-xs uppercase tracking-[0.2em] text-[#73D8FF]">{{ $step['label'] }}</p>
                            <h3 class="mt-2 text-xl font-bold text-[#E6EEF8]">{{ $step['title'] }}</h3>
                            <p class="mt-1 text-sm text-[#9FB3C8]">{{ count($step['field_keys']) }} champs visibles dans cette étape.</p>
                        </div>
                        <button type="button"
                                class="rounded-full border border-[rgba(0,209,255,0.10)] px-3 py-1 text-xs font-semibold text-[#BFD2E6]"
                                data-step-collapse="{{ $step['index'] }}">
                            Réduire
                        </button>
                    </div>

                    <div class="mt-5 grid gap-5 md:grid-cols-2" data-step-body="{{ $step['index'] }}">
                        @foreach ($step['fields'] as $field)
                            @php
                                $value = $form['values'][$field['field_key']] ?? $field['default_value'] ?? null;
                                $partial = match ($field['field_type']) {
                                    \App\Models\FormField::TYPE_TEXT,
                                    \App\Models\FormField::TYPE_NUMBER,
                                    \App\Models\FormField::TYPE_DATE,
                                    \App\Models\FormField::TYPE_DATETIME => 'forms.runtime.fields.input',
                                    \App\Models\FormField::TYPE_TEXTAREA => 'forms.runtime.fields.textarea',
                                    \App\Models\FormField::TYPE_BOOLEAN => 'forms.runtime.fields.boolean',
                                    \App\Models\FormField::TYPE_FILE => 'forms.runtime.fields.file',
                                    \App\Models\FormField::TYPE_SELECT,
                                    \App\Models\FormField::TYPE_MULTISELECT,
                                    \App\Models\FormField::TYPE_CHECKBOX,
                                    \App\Models\FormField::TYPE_RADIO => 'forms.runtime.fields.choice',
                                    default => 'forms.runtime.fields.selector',
                                };
                            @endphp

                            <div class="space-y-2 rounded-2xl border border-[rgba(255,255,255,0.05)] bg-[rgba(255,255,255,0.02)] p-4">
                                @include($partial, ['field' => $field, 'value' => $value, 'runtimeOptions' => $field['runtime_options'] ?? []])
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-5 flex flex-wrap items-center justify-between gap-3 border-t border-[rgba(0,209,255,0.08)] pt-4">
                        <button type="button"
                                class="dgcpt-btn-outline"
                                data-step-prev
                                @if ($loop->first) disabled @endif>
                            Étape précédente
                        </button>

                        <div class="flex flex-wrap gap-3">
                            <button type="submit" name="action" value="save" class="dgcpt-btn-outline">Enregistrer brouillon</button>
                            @if (! $loop->last)
                                <button type="button" class="dgcpt-btn-primary" data-step-next>Étape suivante</button>
                            @else
                                <button type="submit" name="action" value="complete" class="dgcpt-btn-primary">Valider l’étape</button>
                            @endif
                        </div>
                    </div>
                </section>
            @endforeach
        </form>

        <div class="space-y-4">
            @include('forms.runtime.attachments-panel', ['attachmentFields' => $attachmentFields])
            <div class="rounded-2xl border border-[rgba(0,209,255,0.10)] bg-[rgba(5,8,22,0.72)] p-4 text-sm text-[#BFD2E6]">
                <p class="font-semibold text-[#E6EEF8]">Mode plein écran</p>
                <p class="mt-2 text-xs text-[#9FB3C8]">La structure wizard privilégie une saisie immersive desktop-first avec navigation progressive et sections repliables.</p>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const wizardRoot = document.querySelector('[data-runtime-wizard]');
        if (!wizardRoot) {
            return;
        }

        const steps = [...wizardRoot.querySelectorAll('.wizard-step')];
        const triggers = [...wizardRoot.querySelectorAll('[data-step-trigger]')];
        const form = wizardRoot.querySelector('[data-autosave-form]');
        const autosaveIndicator = wizardRoot.querySelector('[data-autosave-indicator]');
        let currentStep = 0;
        let autosaveTimer = null;

        const showStep = (index) => {
            currentStep = Math.max(0, Math.min(index, steps.length - 1));
            steps.forEach((step, stepIndex) => {
                step.hidden = stepIndex !== currentStep;
            });
            triggers.forEach((trigger, triggerIndex) => {
                const active = triggerIndex === currentStep;
                trigger.dataset.active = active ? 'true' : 'false';
                trigger.classList.toggle('border-[rgba(0,168,107,0.25)]', active);
                trigger.classList.toggle('bg-[rgba(0,168,107,0.10)]', active);
                trigger.classList.toggle('text-[#7EF2BE]', active);
            });
        };

        const scheduleAutosave = () => {
            if (!form) {
                return;
            }

            clearTimeout(autosaveTimer);
            autosaveTimer = setTimeout(async () => {
                const payload = new FormData(form);
                payload.set('action', 'save');

                try {
                    if (autosaveIndicator) {
                        autosaveIndicator.textContent = 'SAVING';
                    }

                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'Accept': 'text/html',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: payload,
                    });

                    if (response.ok && autosaveIndicator) {
                        autosaveIndicator.textContent = 'SAVED';
                    }
                } catch (error) {
                    if (autosaveIndicator) {
                        autosaveIndicator.textContent = 'ERROR';
                    }
                    console.warn('Dynamic form autosave failed.', error);
                }
            }, {{ (int) (($autosaveData['interval_seconds'] ?? 20) * 1000) }});
        };

        showStep(0);

        triggers.forEach((trigger) => {
            trigger.addEventListener('click', () => showStep(Number(trigger.dataset.stepTrigger)));
        });

        wizardRoot.querySelectorAll('[data-step-next]').forEach((button) => {
            button.addEventListener('click', () => showStep(currentStep + 1));
        });

        wizardRoot.querySelectorAll('[data-step-prev]').forEach((button) => {
            button.addEventListener('click', () => showStep(currentStep - 1));
        });

        wizardRoot.querySelectorAll('[data-step-collapse]').forEach((button) => {
            button.addEventListener('click', () => {
                const body = wizardRoot.querySelector(`[data-step-body="${button.dataset.stepCollapse}"]`);
                if (!body) {
                    return;
                }

                body.hidden = !body.hidden;
                button.textContent = body.hidden ? 'Déplier' : 'Réduire';
            });
        });

        form?.addEventListener('input', scheduleAutosave);
        form?.addEventListener('change', scheduleAutosave);
    });
</script>
