<x-app-layout>
    <motion.div class="mx-auto max-w-3xl space-y-8 px-0 py-2" data-motion="fade">
        <motion.div data-motion="fade">
            <p class="dgcpt-card-title">Import pont DGCPT</p>
            <h1 class="dgcpt-page-title">Importer un questionnaire</h1>
            <p class="mt-2 text-sm text-[#9FB3C8]">
                Les fichiers DOCX/XLSX sont convertis en <strong class="text-[#E6EEF8]">QuestionnaireTemplate</strong>
                (brouillon) — le runtime questionnaire existant est réutilisé tel quel.
            </p>
        </motion.div>

        <motion.form method="POST" action="{{ route('dgcpt.questionnaire-import.store') }}" enctype="multipart/form-data" class="dgcpt-surface space-y-5 p-6" data-motion="fade">
            @csrf
            <div>
                <label class="dgcpt-label">Fichier (DOCX, XLSX)</label>
                <input type="file" name="file" accept=".docx,.xlsx,.xls" required class="mt-1 w-full text-sm text-[#BFD2E6]">
                <p class="mt-1 text-xs text-[#9FB3C8]">Ex. TP_Lambarene.docx — détection entité / domaine / template national.</p>
            </div>
            <div>
                <label class="dgcpt-label">Nom du modèle (optionnel)</label>
                <input type="text" name="name" class="dgcpt-input w-full" placeholder="Audit SI — TP Lambaréné">
            </div>
            <div class="flex gap-3">
                <button type="submit" class="dgcpt-btn-primary">Importer vers le concepteur</button>
                <a href="{{ route('dgcpt.hierarchy.index') }}" class="dgcpt-btn-outline">Retour hiérarchie</a>
            </div>
        </motion.form>
    </motion.div>
</x-app-layout>
