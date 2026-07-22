<x-app-layout>
    <div class="mx-auto max-w-3xl space-y-8 px-0 py-2" data-motion="fade">
        <div class="rounded-2xl border border-[rgba(0,209,255,.20)] bg-[#071220] p-6 shadow-xl" data-motion="fade">
            <p class="dgcpt-card-title">Import pont DGCPT</p>
            <h1 class="dgcpt-page-title">Importer un questionnaire</h1>
            <p class="mt-2 text-sm text-[#9FB3C8]">
                Les fichiers DOCX sont convertis en modèles structurés : thème, thématiques, sous-thématiques,
                questions, documents attendus et interlocuteurs.
            </p>
        </div>

        @if ($errors->any())
            <div class="rounded-2xl border border-[rgba(255,90,90,.45)] bg-[#2A1018] px-5 py-4 text-sm text-[#FFD4D4] shadow-lg" role="alert">
                <p class="font-bold">L’import n’a pas pu être effectué.</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('dgcpt.questionnaire-import.store') }}" enctype="multipart/form-data" class="space-y-5 rounded-2xl border border-[rgba(0,209,255,.30)] bg-[#071220] p-6 text-[#E6EEF8] shadow-xl" data-motion="fade">
            @csrf
            <div>
                <label class="dgcpt-label">Fichier questionnaire (.docx)</label>
                <input type="file" name="file" accept=".docx" required class="mt-2 block w-full rounded-xl border border-[rgba(0,209,255,.25)] bg-[#020817] px-4 py-3 text-sm text-[#E6EEF8] file:mr-4 file:rounded-lg file:border-0 file:bg-[#123D5A] file:px-4 file:py-2 file:font-semibold file:text-white hover:file:bg-[#175078]">
                <p class="mt-1 text-xs text-[#9FB3C8]">Les anciens fichiers .doc doivent d’abord être enregistrés au format .docx.</p>
            </div>
            <div>
                <label class="dgcpt-label">Nom du modèle (optionnel)</label>
                <input type="text" name="name" class="dgcpt-input w-full" placeholder="Audit SI — TP Lambaréné">
            </div>
            <label class="flex items-start gap-3 rounded-xl border border-[rgba(0,209,255,.28)] bg-[#020817] p-4 text-sm text-[#D6E5F5]">
                <input type="hidden" name="publish_now" value="0">
                <input type="checkbox" name="publish_now" value="1" checked class="mt-1 rounded border-[#37617C] bg-[#071220]">
                <span><strong class="text-[#E6EEF8]">Publier immédiatement</strong><br>Le questionnaire sera proposé lors de la création des groupes d’audit et pourra être cloné comme base de travail.</span>
            </label>
            <div class="flex gap-3">
                <button type="submit" class="dgcpt-btn-primary">Importer vers le concepteur</button>
                <a href="{{ route('dgcpt.hierarchy.index') }}" class="dgcpt-btn-outline">Retour hiérarchie</a>
            </div>
        </form>
    </div>
</x-app-layout>
