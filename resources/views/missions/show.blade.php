<x-app-layout>
    @php
        $labels = [
            'demarrer' => 'Passer en cours',
            'cloturer' => 'Clôturer la mission',
            'valider_is' => 'Valider (Inspection des Services)',
            'demander_corrections' => 'Demander des corrections',
            'valider_copri' => 'Valider stratégiquement (COPRI)',
            'renvoyer_copri' => 'Renvoyer pour révision (COPRI)',
        ];
    @endphp

    <div class="mx-auto max-w-4xl space-y-8 px-0 py-2">
        @if (session('status'))
            <div class="dgcpt-surface border-[#00A86B]/35 px-4 py-3 text-sm text-[#E6EEF8] ring-1 ring-[rgba(0,168,107,0.25)]">
                {{ session('status') }}
            </div>
        @endif

        <div class="space-y-2">
            <p class="dgcpt-card-title">Fiche mission</p>
            <h1 class="dgcpt-page-title">{{ $mission->organisation }}</h1>
            <p class="flex flex-wrap items-center gap-2 text-sm text-[#9FB3C8]">
                <span>État workflow :</span>
                <x-mission-status-badge :status="$mission->mission_status" />
            </p>
            @if ($mission->department)
                <p class="text-sm text-[#E6EEF8]">
                    <span class="font-mono font-semibold text-[#00D1FF]">{{ $mission->department->code }}</span>
                    — {{ $mission->department->name }}
                </p>
            @endif
        </div>

        <div class="dgcpt-surface p-6 shadow-sm">
            <h2 class="text-lg font-bold uppercase tracking-wide text-[#E6EEF8]">Description</h2>
            <p class="mt-2 whitespace-pre-wrap text-sm leading-relaxed text-[#9FB3C8]">{{ $mission->description ?: '—' }}</p>
            <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-[#9FB3C8]">Début</dt>
                    <dd class="font-semibold text-[#E6EEF8]">{{ $mission->date_debut }}</dd>
                </div>
                <div>
                    <dt class="text-[#9FB3C8]">Fin</dt>
                    <dd class="font-semibold text-[#E6EEF8]">{{ $mission->date_fin ?: '—' }}</dd>
                </div>
            </dl>
            @can('update', $mission)
                <p class="mt-4">
                    <a href="{{ route('missions.edit', $mission) }}" class="text-sm font-semibold text-[#00D1FF] hover:underline">
                        Modifier les informations
                    </a>
                </p>
            @endcan
        </div>

        @if (count($allowedActions) > 0)
            <div class="dgcpt-surface border-[#00D1FF]/25 p-6 shadow-sm ring-1 ring-[rgba(0,209,255,0.2)]">
                <h2 class="text-lg font-bold uppercase tracking-wide text-[#E6EEF8]">Décision institutionnelle</h2>
                <p class="mt-1 text-sm text-[#9FB3C8]">
                    Chaque transition est journalisée avec votre compte et un commentaire lorsque requis.
                </p>
                <form method="post" action="{{ route('missions.workflow', $mission) }}" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <label class="dgcpt-card-title mb-1 block">Action</label>
                        <select name="action" required class="mt-1 block w-full rounded-lg border border-[rgba(0,209,255,0.22)] bg-[#050816] px-3 py-2 text-sm text-[#E6EEF8] focus:border-[#00D1FF] focus:outline-none focus:ring-1 focus:ring-[#00D1FF]">
                            @foreach ($allowedActions as $action)
                                <option value="{{ $action }}">{{ $labels[$action] ?? $action }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="dgcpt-card-title mb-1 block">Commentaire</label>
                        <textarea name="comment" rows="3" class="mt-1 block w-full rounded-lg border border-[rgba(0,209,255,0.22)] bg-[#050816] px-3 py-2 text-sm text-[#E6EEF8] placeholder:text-[#9FB3C8]/60 focus:border-[#00D1FF] focus:outline-none focus:ring-1 focus:ring-[#00D1FF]" placeholder="Obligatoire pour demande de corrections ou renvoi COPRI"></textarea>
                        @error('comment')
                            <p class="mt-1 text-sm text-[#FF5A5A]">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit" class="rounded-xl bg-gradient-to-r from-[#0A2A66] to-blue-950 px-4 py-2 text-sm font-bold uppercase tracking-wider text-white ring-1 ring-[rgba(0,209,255,0.3)]">
                        Enregistrer la décision
                    </button>
                </form>
            </div>
        @endif

        <div class="dgcpt-surface p-6 shadow-sm">
            <h2 class="text-lg font-bold uppercase tracking-wide text-[#E6EEF8]">Journal des validations</h2>
            @if ($mission->workflowEvents->isEmpty())
                <p class="mt-2 text-sm text-[#9FB3C8]">Aucune transition enregistrée pour le moment.</p>
            @else
                <ul class="mt-4 divide-y divide-[rgba(0,209,255,0.12)]">
                    @foreach ($mission->workflowEvents as $event)
                        <li class="py-3 text-sm">
                            <div class="flex flex-wrap items-baseline justify-between gap-2">
                                <span class="font-semibold text-[#E6EEF8]">
                                    {{ $labels[$event->action] ?? $event->action }}
                                </span>
                                <span class="text-xs text-[#9FB3C8]">{{ $event->created_at?->format('d/m/Y H:i') }}</span>
                            </div>
                            <p class="text-xs text-[#9FB3C8]">
                                {{ $event->from_status }} → {{ $event->to_status }}
                                @if ($event->user)
                                    — {{ $event->user->displayName() }}
                                @endif
                            </p>
                            @if ($event->comment)
                                <p class="mt-1 text-[#E6EEF8]">{{ $event->comment }}</p>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="flex flex-wrap gap-4 text-sm">
            <a href="{{ route('services.index', $mission) }}" class="font-semibold text-[#00D1FF] hover:underline">Services audités</a>
            <a href="{{ route('processus.index', $mission) }}" class="font-semibold text-[#00D1FF] hover:underline">Processus</a>
            <a href="{{ route('cartographie.index', $mission) }}" class="font-semibold text-[#00D1FF] hover:underline">Cartographie</a>
            <a href="{{ route('missions.rapport', $mission) }}" class="font-semibold text-[#00D1FF] hover:underline">Rapport PDF</a>
            <a href="{{ route('missions.index') }}" class="text-[#9FB3C8] hover:text-[#E6EEF8] hover:underline">← Liste des missions</a>
        </div>
    </div>
</x-app-layout>
