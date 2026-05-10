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

    <div class="max-w-4xl mx-auto px-4 py-10 space-y-8">
        @if (session('status'))
            <div class="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-900 dark:border-green-800 dark:bg-green-900/20 dark:text-green-100">
                {{ session('status') }}
            </div>
        @endif

        <div class="space-y-2">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Fiche mission</p>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $mission->organisation }}</h1>
            <p class="text-sm text-gray-600 dark:text-gray-300">
                État workflow :
                <span class="font-semibold text-indigo-700 dark:text-indigo-300">{{ $mission->mission_status }}</span>
            </p>
            @if ($mission->department)
                <p class="text-sm text-gray-700 dark:text-gray-200">
                    <span class="font-mono font-semibold">{{ $mission->department->code }}</span>
                    — {{ $mission->department->name }}
                </p>
            @endif
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Description</h2>
            <p class="mt-2 whitespace-pre-wrap text-sm text-gray-700 dark:text-gray-300">{{ $mission->description ?: '—' }}</p>
            <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Début</dt>
                    <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $mission->date_debut }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Fin</dt>
                    <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $mission->date_fin ?: '—' }}</dd>
                </div>
            </dl>
            @can('update', $mission)
                <p class="mt-4">
                    <a href="{{ route('missions.edit', $mission) }}" class="text-sm font-semibold text-indigo-600 hover:underline dark:text-indigo-400">
                        Modifier les informations
                    </a>
                </p>
            @endcan
        </div>

        @if (count($allowedActions) > 0)
            <div class="rounded-lg border border-indigo-200 bg-indigo-50/80 p-6 dark:border-indigo-900 dark:bg-indigo-950/40">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Décision institutionnelle</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Chaque transition est journalisée avec votre compte et un commentaire lorsque requis.
                </p>
                <form method="post" action="{{ route('missions.workflow', $mission) }}" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Action</label>
                        <select name="action" required class="mt-1 block w-full rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900">
                            @foreach ($allowedActions as $action)
                                <option value="{{ $action }}">{{ $labels[$action] ?? $action }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Commentaire</label>
                        <textarea name="comment" rows="3" class="mt-1 block w-full rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900" placeholder="Obligatoire pour demande de corrections ou renvoi COPRI"></textarea>
                        @error('comment')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-indigo-500">
                        Enregistrer la décision
                    </button>
                </form>
            </div>
        @endif

        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Journal des validations</h2>
            @if ($mission->workflowEvents->isEmpty())
                <p class="mt-2 text-sm text-gray-500">Aucune transition enregistrée pour le moment.</p>
            @else
                <ul class="mt-4 divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($mission->workflowEvents as $event)
                        <li class="py-3 text-sm">
                            <div class="flex flex-wrap items-baseline justify-between gap-2">
                                <span class="font-medium text-gray-900 dark:text-gray-100">
                                    {{ $labels[$event->action] ?? $event->action }}
                                </span>
                                <span class="text-xs text-gray-500">{{ $event->created_at?->format('d/m/Y H:i') }}</span>
                            </div>
                            <p class="text-xs text-gray-500">
                                {{ $event->from_status }} → {{ $event->to_status }}
                                @if ($event->user)
                                    — {{ $event->user->displayName() }}
                                @endif
                            </p>
                            @if ($event->comment)
                                <p class="mt-1 text-gray-700 dark:text-gray-300">{{ $event->comment }}</p>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="flex flex-wrap gap-4 text-sm">
            <a href="{{ route('services.index', $mission) }}" class="font-semibold text-indigo-600 hover:underline dark:text-indigo-400">Services audités</a>
            <a href="{{ route('processus.index', $mission) }}" class="font-semibold text-indigo-600 hover:underline dark:text-indigo-400">Processus</a>
            <a href="{{ route('cartographie.index', $mission) }}" class="font-semibold text-indigo-600 hover:underline dark:text-indigo-400">Cartographie</a>
            <a href="{{ route('missions.rapport', $mission) }}" class="font-semibold text-indigo-600 hover:underline dark:text-indigo-400">Rapport PDF</a>
            <a href="{{ route('missions.index') }}" class="text-gray-600 hover:underline dark:text-gray-400">← Liste des missions</a>
        </div>
    </div>
</x-app-layout>
