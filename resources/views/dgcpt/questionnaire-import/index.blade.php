<x-app-layout>
    <motion.div class="mx-auto max-w-3xl space-y-8 px-0 py-2" data-motion="fade">
        <motion.div data-motion="fade">
            <p class="dgcpt-card-title">Import pont DGCPT</p>
            <h1 class="dgcpt-page-title">Importer un questionnaire</h1>
            <p class="mt-2 text-sm text-[#9FB3C8]">
                Les fichiers DOCX sont convertis en modèles structurés : thème, thématiques, sous-thématiques,
                questions, documents attendus et interlocuteurs.
            </p>
        </motion.div>

        <motion.form method="POST" action="{{ route('dgcpt.questionnaire-import.store') }}" enctype="multipart/form-data" class="dgcpt-surface space-y-5 p-6" data-motion="fade">
            @csrf
            <div>
                <label class="dgcpt-label">Fichier questionnaire (.docx)</label>
                <input type="file" name="file" accept=".docx" required class="mt-1 w-full text-sm text-[#BFD2E6]">
                <p class="mt-1 text-xs text-[#9FB3C8]">Les anciens fichiers .doc doivent d’abord être enregistrés au format .docx.</p>
            </div>
            <div>
                <label class="dgcpt-label">Nom du modèle (optionnel)</label>
                <input type="text" name="name" class="dgcpt-input w-full" placeholder="Audit SI — TP Lambaréné">
            </div>
            <label class="flex items-start gap-3 rounded-xl border border-[rgba(0,209,255,.14)] p-4 text-sm text-[#BFD2E6]">
                <input type="hidden" name="publish_now" value="0">
                <input type="checkbox" name="publish_now" value="1" checked class="mt-1 rounded border-[#37617C] bg-[#071220]">
                <span><strong class="text-[#E6EEF8]">Publier immédiatement</strong><br>Le questionnaire sera proposé lors de la création des groupes d’audit et pourra être cloné comme base de travail.</span>
            </label>
            <div class="flex gap-3">
                <button type="submit" class="dgcpt-btn-primary">Importer vers le concepteur</button>
                <a href="{{ route('dgcpt.hierarchy.index') }}" class="dgcpt-btn-outline">Retour hiérarchie</a>
            </div>
        </motion.form>
    </motion.div>
</x-app-layout>
