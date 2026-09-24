<x-app-layout>
<div class="mx-auto max-w-6xl space-y-5">
    <a href="{{ route('cfdt.index') }}">← CFDT</a>
    @if(session('status'))<div class="dgcpt-surface border border-emerald-500/40 p-4 text-emerald-300">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="dgcpt-surface border border-red-500/50 p-4 text-red-200"><p class="font-black">L’opération n’a pas été enregistrée.</p><ul class="mt-2 list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    @php($statusLabels = ['draft'=>'Brouillon','pending_review'=>'En attente de validation','changes_requested'=>'Renvoyé pour traitement','published'=>'Validé et publié'])
    <section class="dgcpt-surface p-6">
        <div class="flex flex-wrap items-start justify-between gap-3"><div><p class="dgcpt-card-title">{{ $course->code }}</p><h1 class="dgcpt-page-title">{{ $course->title }}</h1></div><span class="rounded-full border border-cyan-700 px-3 py-1 text-sm">{{ $statusLabels[$course->status] ?? $course->status }}</span></div>
        <p>{{ $course->description }}</p>
        @foreach($course->content ?? [] as $lesson)<h2 class="mt-4 font-bold">{{ $lesson['title'] }}</h2><p class="whitespace-pre-line">{{ $lesson['body'] }}</p>@endforeach
        @if($course->review_observation)<div class="mt-5 rounded-xl border border-amber-500/50 bg-amber-950/20 p-4"><p class="font-black text-amber-200">Observations du superviseur</p><p class="mt-2 whitespace-pre-line">{{ $course->review_observation }}</p></div>@endif
    </section>

    @if($course->created_by === auth()->id() || $canReview)
    <section class="dgcpt-surface p-5">
        <div class="flex items-center justify-between gap-3"><div><h2 class="text-xl font-black">QCM soumis</h2><p class="dgcpt-text-muted">{{ count($course->questions ?? []) }} question(s) · seuil de réussite {{ $course->pass_mark }} %</p></div></div>
        <div class="mt-4 space-y-4">
            @forelse($course->questions ?? [] as $index => $question)
            <article class="rounded-xl border border-slate-700 p-4"><div class="flex justify-between gap-3"><h3 class="font-black">{{ $index + 1 }}. {{ $question['text'] }}</h3><span>{{ $question['points'] }} point(s)</span></div><p class="mt-1 text-xs dgcpt-text-muted">{{ ($question['type'] ?? 'single') === 'multiple' ? 'Choix multiples' : 'Choix unique' }}</p><ol class="mt-3 space-y-2">@foreach($question['options'] ?? [] as $optionIndex => $option)<li class="rounded-lg border px-3 py-2 {{ in_array($optionIndex, $question['correct'] ?? []) ? 'border-emerald-500/60 text-emerald-200' : 'border-slate-800' }}">{{ chr(65 + $optionIndex) }}. {{ $option }} @if(in_array($optionIndex, $question['correct'] ?? []))<span class="ml-2 text-xs">✓ bonne réponse</span>@endif</li>@endforeach</ol>@if(!empty($question['explanation']))<p class="mt-3 text-sm"><strong>Explication :</strong> {{ $question['explanation'] }}</p>@endif</article>
            @empty<div class="rounded-xl border border-amber-600/40 p-4 text-amber-200">Ce QCM ne contient encore aucune question.</div>@endforelse
        </div>
    </section>
    @endif

    @if($canEdit)
    <section class="dgcpt-surface p-5"><h2 class="font-black">Ajouter une question</h2><form method="post" action="{{ route('cfdt.questions.store',$course) }}" class="space-y-3">@csrf<input class="dgcpt-input" name="text" placeholder="Question" required><select class="dgcpt-select" name="type"><option value="single">Choix unique</option><option value="multiple">Choix multiples</option></select>@for($i=0;$i<4;$i++)<div class="flex gap-2"><input class="dgcpt-input" name="options[]" placeholder="Réponse {{ $i+1 }}" required><label><input type="checkbox" name="correct[]" value="{{ $i }}"> Correcte</label></div>@endfor<textarea class="dgcpt-textarea" name="explanation" placeholder="Explication"></textarea><input class="dgcpt-input" type="number" name="points" value="1" min="1"><button class="dgcpt-btn-primary">Ajouter</button></form></section>
    <form method="post" action="{{ route('cfdt.submit-review',$course) }}">@csrf @method('PATCH')<button class="dgcpt-btn-primary">Soumettre au superviseur</button></form>
    @endif

    @if($canReview && $course->status === 'pending_review')
    <section class="dgcpt-surface p-5"><h2 class="text-xl font-black">Décision du superviseur</h2><p class="dgcpt-text-muted">Le contenu ci-dessus est présenté en lecture seule. Une observation est obligatoire en cas de renvoi.</p><form method="post" action="{{ route('cfdt.review',$course) }}" class="mt-4 space-y-4">@csrf @method('PATCH')<textarea class="dgcpt-textarea" name="review_observation" rows="5" placeholder="Notes d’observation ou raisons du renvoi">{{ old('review_observation') }}</textarea><div class="flex flex-wrap gap-3"><button class="dgcpt-btn-primary" name="decision" value="approve">Valider et publier</button><button class="dgcpt-btn-secondary" name="decision" value="return">Renvoyer pour traitement</button></div></form></section>
    @endif

    @if($canAssign)
    <section class="dgcpt-surface p-5"><h2 class="font-black">Affecter le test validé</h2><p class="dgcpt-text-muted">Sélectionnez au moins un agent, une structure ou une catégorie professionnelle. Les critères se cumulent par union.</p><form method="post" action="{{ route('cfdt.enroll',$course) }}" class="mt-3 grid gap-4 md:grid-cols-2">@csrf<div><label class="font-bold">Agents nommément désignés</label><select class="dgcpt-select mt-2 min-h-48" name="user_ids[]" multiple>@foreach($users as $user)<option value="{{ $user->id }}" @selected(in_array($user->id, array_map('intval', old('user_ids', []))))>{{ $user->prenom }} {{ $user->name }} — {{ $user->department?->name ?? 'Sans structure' }}</option>@endforeach</select></div><div><label class="font-bold">Directions, administrations ou départements</label><select class="dgcpt-select mt-2 min-h-48" name="department_ids[]" multiple>@foreach($departments as $department)<option value="{{ $department->id }}" @selected(in_array($department->id, array_map('intval', old('department_ids', []))))>{{ $department->code }} — {{ $department->name }}</option>@endforeach</select><label class="mt-2 block"><input type="checkbox" name="include_descendants" value="1" @checked(old('include_descendants', true))> Inclure toutes les sous-structures</label></div><div><label class="font-bold">Catégories professionnelles</label><select class="dgcpt-select mt-2 min-h-40" name="role_categories[]" multiple>@foreach($roleCategories as $value=>$label)<option value="{{ $value }}" @selected(in_array($value, old('role_categories', [])))>{{ $label }}</option>@endforeach</select></div><div><label class="font-bold">Date et heure limites</label><input class="dgcpt-input mt-2" type="datetime-local" name="expires_at" value="{{ old('expires_at', now()->addDays(7)->format('Y-m-d\TH:i')) }}" min="{{ now()->addMinute()->format('Y-m-d\TH:i') }}" required><p class="mt-2 dgcpt-text-muted">Une notification interne et un courriel personnel seront envoyés.</p></div><div class="md:col-span-2"><button class="dgcpt-btn-primary">Affecter et notifier</button></div></form></section>
    @endif

    @if($enrollment && $course->status === 'published')<a class="dgcpt-btn-primary" href="{{ route('cfdt.attempt',$course) }}">Passer le QCM</a>@if($enrollment->certificate)<a class="dgcpt-btn-secondary" href="{{ route('cfdt.certificate',$enrollment->certificate) }}">Télécharger mon certificat</a>@endif @endif
</div>
</x-app-layout>
