<x-app-layout>
    <div class="mx-auto max-w-4xl space-y-8 px-0 py-2">
        @if (session('status'))
            <div class="dgcpt-surface border-[#00A86B]/35 px-4 py-3 text-sm text-[#E6EEF8] ring-1 ring-[rgba(0,168,107,0.25)]">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="dgcpt-surface border-[rgba(255,90,90,0.30)] px-4 py-3 text-sm text-[#FFD4D4] ring-1 ring-[rgba(255,90,90,0.18)]">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="dgcpt-surface p-8 shadow-sm">
            <p class="dgcpt-card-title">Workflow Runtime</p>
            <h1 class="mt-2 text-3xl font-bold text-[#E6EEF8]">{{ $stage->name }}</h1>
            <p class="mt-3 text-sm text-[#9FB3C8]">
                Ce stage n’utilise pas un formulaire dédié. La validation s’appuie sur la logique métier existante ou sur une confirmation manuelle.
            </p>

            <div class="mt-6 rounded-2xl border border-[rgba(0,209,255,0.12)] bg-[rgba(5,8,22,0.72)] p-5 text-sm text-[#BFD2E6]">
                <p><span class="font-semibold text-[#E6EEF8]">Mission :</span> #{{ $instance->mission_id }}</p>
                <p><span class="font-semibold text-[#E6EEF8]">Type :</span> {{ $stage->resolvedStageType()?->label() ?? 'Stage' }}</p>
                <p><span class="font-semibold text-[#E6EEF8]">Composant :</span> {{ $stage->resolvedComponentKey() }}</p>
            </div>

            <form method="POST" action="{{ route('workflow-runtime.stage.submit', ['mission' => $instance->mission_id, 'stage' => $stage]) }}" class="mt-6 flex flex-wrap gap-3">
                @csrf
                <button type="submit" class="dgcpt-btn-primary">Valider l’étape</button>
                <a href="{{ route('missions.show', $instance->mission_id) }}" class="dgcpt-btn-outline">Retour mission</a>
            </form>
        </div>
    </div>
</x-app-layout>
