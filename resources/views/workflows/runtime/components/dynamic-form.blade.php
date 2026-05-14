<x-app-layout>
    <div class="mx-auto max-w-5xl space-y-8 px-0 py-2">
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

        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="dgcpt-card-title">Workflow Runtime</p>
                <h1 class="dgcpt-page-title">{{ $stage->name }}</h1>
                <p class="mt-1 text-sm text-[#9FB3C8]">Mission #{{ $instance->mission_id }} · composant {{ $stage->resolvedComponentKey() }}</p>
            </div>
            <a href="{{ route('missions.show', $instance->mission_id) }}" class="dgcpt-btn-outline">Retour mission</a>
        </div>

        @include('forms.runtime.index', [
            'form' => $form,
            'stage' => $stage,
            'instance' => $instance,
            'entretien' => $entretien,
            'wizard' => $wizard ?? null,
            'autosave' => $autosave ?? null,
        ])
    </div>
</x-app-layout>
