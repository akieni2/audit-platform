<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-6 px-0 py-2">
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="dgcpt-card-title">Assistant questionnaire · {{ $mission->organisation }}</p>
                <h1 class="dgcpt-page-title">Création visuelle guidée</h1>
                <p class="mt-2 max-w-3xl text-sm text-[#9FB3C8]">Construisez le questionnaire niveau par niveau. Il restera exclusivement lié à cette mission et sera proposé lors de la constitution des équipes.</p>
            </div>
            <a href="{{ route('missions.show', $mission) }}" class="dgcpt-btn-outline">Retour à la mission</a>
        </header>

        @if ($errors->any())
            <div class="rounded-xl border border-[#FF5A5A]/40 bg-[#3A1A20] p-4 text-sm text-[#FFB4B4]">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]" data-questionnaire-wizard>
            <section class="dgcpt-surface overflow-hidden p-0">
                <div class="border-b border-[rgba(0,209,255,.15)] bg-[rgba(0,209,255,.05)] px-6 py-5">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[.2em] text-[#73D8FF]" data-step-label>Étape 1 sur 4</p>
                            <h2 class="mt-1 text-xl font-bold text-[#E6EEF8]" data-step-title>Quel est le thème du questionnaire ?</h2>
                        </div>
                        <div class="flex gap-2" aria-hidden="true">
                            @foreach(range(1, 4) as $step)
                                <span class="h-2.5 w-10 rounded-full bg-[#17304A]" data-step-dot="{{ $step }}"></span>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    <div data-panel="theme">
                        <label class="dgcpt-label" for="wizard-theme">Nom du thème</label>
                        <input id="wizard-theme" class="dgcpt-input mt-2" maxlength="255" placeholder="Exemple : ALIGNEMENT STRATÉGIQUE">
                        <p class="mt-2 text-xs text-[#9FB3C8]">Le thème deviendra le titre principal du questionnaire.</p>
                        <button type="button" class="dgcpt-btn-primary mt-6" data-action="save-theme">Sauvegarder et continuer</button>
                    </div>

                    <div class="hidden" data-panel="thematic">
                        <label class="dgcpt-label" for="wizard-thematic">Titre de la thématique</label>
                        <input id="wizard-thematic" class="dgcpt-input mt-2" maxlength="255" placeholder="Exemple : Alignement du SDSI">
                        <button type="button" class="dgcpt-btn-primary mt-6" data-action="save-thematic">Sauvegarder et continuer</button>
                    </div>

                    <div class="hidden" data-panel="subtheme">
                        <label class="dgcpt-label" for="wizard-subtheme">Titre de la sous-thématique</label>
                        <input id="wizard-subtheme" class="dgcpt-input mt-2" maxlength="255" placeholder="Exemple : Vision et stratégie">
                        <button type="button" class="dgcpt-btn-primary mt-6" data-action="save-subtheme">Sauvegarder et créer la première question</button>
                    </div>

                    <div class="hidden space-y-5" data-panel="question">
                        <div>
                            <label class="dgcpt-label" for="wizard-question">Question posée à l’audité</label>
                            <textarea id="wizard-question" rows="3" class="dgcpt-textarea mt-2" placeholder="Saisissez la question complète..."></textarea>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="dgcpt-label" for="wizard-question-type">Réponse attendue</label>
                                <select id="wizard-question-type" class="dgcpt-select mt-2">
                                    <option value="textarea">Réponse détaillée</option>
                                    <option value="text">Réponse courte</option>
                                    <option value="boolean_na">Oui / Non / N.A.</option>
                                    <option value="date">Date</option>
                                    <option value="number">Nombre</option>
                                    <option value="risk_capture">Capture d’un risque</option>
                                </select>
                            </div>
                            <div>
                                <label class="dgcpt-label" for="wizard-documents">Documents attendus</label>
                                <input id="wizard-documents" class="dgcpt-input mt-2" placeholder="Exemple : SDSI validé, portefeuille projets">
                            </div>
                        </div>
                        <div>
                            <label class="dgcpt-label" for="wizard-help">Précisions pour l’auditeur</label>
                            <textarea id="wizard-help" rows="2" class="dgcpt-textarea mt-2" placeholder="Critères, éléments à vérifier, consignes..."></textarea>
                        </div>
                        <div class="grid gap-3 text-sm sm:grid-cols-3">
                            <label class="flex items-center gap-2 text-[#BFD2E6]"><input id="wizard-required" type="checkbox" checked> Réponse obligatoire</label>
                            <label class="flex items-center gap-2 text-[#BFD2E6]"><input id="wizard-observation" type="checkbox" checked> Autoriser une observation</label>
                            <label class="flex items-center gap-2 text-[#BFD2E6]"><input id="wizard-risk" type="checkbox" checked> Détecter un risque</label>
                        </div>
                        <button type="button" class="dgcpt-btn-primary" data-action="save-question">Sauvegarder la question</button>
                    </div>

                    <div class="hidden rounded-xl border border-[rgba(126,242,190,.25)] bg-[rgba(0,168,107,.07)] p-5" data-panel="next-action">
                        <p class="font-semibold text-[#E6EEF8]">La question a été sauvegardée. Que souhaitez-vous faire ?</p>
                        <div class="mt-4 flex flex-wrap gap-3">
                            <button type="button" class="dgcpt-btn-primary" data-action="another-question">Ajouter une autre question</button>
                            <button type="button" class="dgcpt-btn-outline" data-action="another-subtheme">Ajouter une sous-thématique</button>
                            <button type="button" class="dgcpt-btn-outline" data-action="another-thematic">Ajouter une thématique</button>
                            <button type="button" class="rounded-xl bg-[#00A86B] px-4 py-2 text-sm font-bold text-white" data-action="finish">Terminer le questionnaire</button>
                        </div>
                    </div>

                    <p class="mt-5 hidden text-sm text-[#FF8A8A]" data-error></p>
                </div>
            </section>

            <aside class="dgcpt-surface p-5 lg:sticky lg:top-4 lg:self-start">
                <p class="dgcpt-card-title">Aperçu en direct</p>
                <div class="mt-4 space-y-3 text-sm" data-preview>
                    <p class="text-[#9FB3C8]">Le plan du questionnaire apparaîtra ici.</p>
                </div>
                <div class="mt-5 border-t border-[rgba(0,209,255,.15)] pt-4 text-xs text-[#9FB3C8]" data-summary>0 question</div>
            </aside>
        </div>

        <form method="POST" action="{{ route('missions.questionnaires.wizard.store', $mission) }}" class="hidden" data-final-form>
            @csrf
            <input type="hidden" name="structure" data-structure>
        </form>
    </div>

    <script>
        (() => {
            const root = document.querySelector('[data-questionnaire-wizard]');
            if (!root) return;
            const state = { theme: '', thematics: [] };
            let thematicIndex = -1;
            let subthemeIndex = -1;
            const panels = [...root.querySelectorAll('[data-panel]')];
            const titles = {
                theme: ['Étape 1 sur 4', 'Quel est le thème du questionnaire ?'],
                thematic: ['Étape 2 sur 4', 'Quelle est la thématique ?'],
                subtheme: ['Étape 3 sur 4', 'Quelle est la sous-thématique ?'],
                question: ['Étape 4 sur 4', 'Créez une question pour cette sous-thématique'],
                'next-action': ['Question sauvegardée', 'Souhaitez-vous continuer ?'],
            };
            const inputs = {
                theme: root.querySelector('#wizard-theme'), thematic: root.querySelector('#wizard-thematic'),
                subtheme: root.querySelector('#wizard-subtheme'), question: root.querySelector('#wizard-question'),
                type: root.querySelector('#wizard-question-type'), documents: root.querySelector('#wizard-documents'),
                help: root.querySelector('#wizard-help'), required: root.querySelector('#wizard-required'),
                observation: root.querySelector('#wizard-observation'), risk: root.querySelector('#wizard-risk'),
            };
            const error = root.querySelector('[data-error]');

            function show(name, step = 4) {
                panels.forEach(panel => panel.classList.toggle('hidden', panel.dataset.panel !== name));
                root.querySelector('[data-step-label]').textContent = titles[name][0];
                root.querySelector('[data-step-title]').textContent = titles[name][1];
                root.querySelectorAll('[data-step-dot]').forEach(dot => {
                    dot.classList.toggle('bg-[#00D1FF]', Number(dot.dataset.stepDot) <= step);
                    dot.classList.toggle('bg-[#17304A]', Number(dot.dataset.stepDot) > step);
                });
                error.classList.add('hidden');
            }
            function fail(message) { error.textContent = message; error.classList.remove('hidden'); }
            function value(input) { return input.value.trim(); }
            function render() {
                const preview = root.querySelector('[data-preview]');
                preview.replaceChildren();
                if (!state.theme) {
                    preview.innerHTML = '<p class="text-[#9FB3C8]">Le plan du questionnaire apparaîtra ici.</p>';
                    return;
                }
                const theme = document.createElement('div');
                theme.className = 'font-bold text-[#00D1FF]'; theme.textContent = state.theme; preview.append(theme);
                state.thematics.forEach((thematic, ti) => {
                    const block = document.createElement('div'); block.className = 'ml-2 border-l border-[#1E5270] pl-3';
                    const title = document.createElement('p'); title.className = 'font-semibold text-[#E6EEF8]'; title.textContent = `${ti + 1}. ${thematic.title}`; block.append(title);
                    thematic.subthemes.forEach((subtheme, si) => {
                        const sub = document.createElement('div'); sub.className = 'mt-2 ml-2';
                        const subTitle = document.createElement('p'); subTitle.className = 'text-[#73D8FF]'; subTitle.textContent = `${ti + 1}.${si + 1} ${subtheme.title}`; sub.append(subTitle);
                        subtheme.questions.forEach((question, qi) => {
                            const q = document.createElement('p'); q.className = 'mt-1 ml-3 text-xs text-[#9FB3C8]'; q.textContent = `Q${qi + 1}. ${question.question}`; sub.append(q);
                        }); block.append(sub);
                    }); preview.append(block);
                });
                const count = state.thematics.reduce((total, thematic) => total + thematic.subthemes.reduce((subtotal, subtheme) => subtotal + subtheme.questions.length, 0), 0);
                root.querySelector('[data-summary]').textContent = `${state.thematics.length} thématique(s) · ${count} question(s)`;
            }
            root.addEventListener('click', event => {
                const action = event.target.closest('[data-action]')?.dataset.action;
                if (!action) return;
                if (action === 'save-theme') {
                    if (!value(inputs.theme)) return fail('Veuillez saisir le thème du questionnaire.');
                    state.theme = value(inputs.theme); render(); show('thematic', 2); inputs.thematic.focus();
                } else if (action === 'save-thematic') {
                    if (!value(inputs.thematic)) return fail('Veuillez saisir le titre de la thématique.');
                    state.thematics.push({ title: value(inputs.thematic), subthemes: [] }); thematicIndex = state.thematics.length - 1;
                    inputs.thematic.value = ''; render(); show('subtheme', 3); inputs.subtheme.focus();
                } else if (action === 'save-subtheme') {
                    if (!value(inputs.subtheme)) return fail('Veuillez saisir le titre de la sous-thématique.');
                    state.thematics[thematicIndex].subthemes.push({ title: value(inputs.subtheme), questions: [] }); subthemeIndex = state.thematics[thematicIndex].subthemes.length - 1;
                    inputs.subtheme.value = ''; render(); show('question', 4); inputs.question.focus();
                } else if (action === 'save-question') {
                    if (!value(inputs.question)) return fail('Veuillez saisir le texte de la question.');
                    state.thematics[thematicIndex].subthemes[subthemeIndex].questions.push({
                        question: value(inputs.question), question_type: inputs.type.value, expected_documents: value(inputs.documents),
                        help_text: value(inputs.help), required: inputs.required.checked, allows_observation: inputs.observation.checked,
                        allows_risk_detection: inputs.risk.checked,
                    });
                    inputs.question.value = ''; inputs.documents.value = ''; inputs.help.value = ''; render(); show('next-action', 4);
                } else if (action === 'another-question') {
                    show('question', 4); inputs.question.focus();
                } else if (action === 'another-subtheme') {
                    show('subtheme', 3); inputs.subtheme.focus();
                } else if (action === 'another-thematic') {
                    show('thematic', 2); inputs.thematic.focus();
                } else if (action === 'finish') {
                    const form = document.querySelector('[data-final-form]');
                    form.querySelector('[data-structure]').value = JSON.stringify(state); form.submit();
                }
            });
            show('theme', 1);
        })();
    </script>
</x-app-layout>
