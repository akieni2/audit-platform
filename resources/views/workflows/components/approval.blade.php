<div class="space-y-4">
    <div class="rounded-2xl border border-[rgba(201,174,255,0.22)] bg-[rgba(28,15,39,0.72)] p-5">
        <p class="text-sm font-semibold text-[#E6EEF8]">{{ $ui['title'] }}</p>
        <p class="mt-2 text-sm text-[#D8B4FE]">Une approbation explicite est attendue sur cette étape. Vous pouvez ouvrir l’étape ou utiliser les actions d’exécution ci-dessous.</p>
    </div>
    <div class="flex flex-wrap gap-3">
        <a href="{{ $ui['action_url'] }}" class="dgcpt-btn-primary">Ouvrir l’approbation</a>
        <form method="POST" action="{{ route('workflow-runtime.transition', $mission) }}">
            @csrf
            <input type="hidden" name="action" value="approve" />
            <input type="hidden" name="stage_id" value="{{ $runtime->instance->currentStage?->id }}" />
            <button type="submit" class="dgcpt-btn-outline">Approuver</button>
        </form>
    </div>
</div>
