<x-app-layout>
<div class="mx-auto max-w-6xl space-y-5 pb-10">
    <a class="dgcpt-btn-secondary" href="{{ route('cfdt.show', $course) }}">← Retour au parcours</a>
    <header><p class="dgcpt-card-title">Support pédagogique</p><h1 class="dgcpt-page-title">{{ $resource->title }}</h1>@if($resource->description)<p class="dgcpt-text-muted">{{ $resource->description }}</p>@endif</header>
    <section class="dgcpt-surface overflow-hidden p-4 sm:p-6">
        @if($resource->type === 'text')
            <article class="prose prose-invert max-w-none whitespace-pre-line leading-relaxed">{{ $resource->body }}</article>
        @elseif($resource->type === 'image')
            <img class="mx-auto max-h-[75vh] max-w-full rounded-xl object-contain" src="{{ route('cfdt.resources.file', [$course, $resource]) }}" alt="{{ $resource->title }}">
        @elseif($resource->type === 'pdf')
            <iframe class="h-[75vh] w-full rounded-xl bg-white" src="{{ route('cfdt.resources.file', [$course, $resource]) }}" title="{{ $resource->title }}"></iframe>
        @elseif($resource->type === 'video' && $resource->youtubeEmbedUrl())
            <div class="aspect-video"><iframe class="h-full w-full rounded-xl" src="{{ $resource->youtubeEmbedUrl() }}" allow="accelerometer; autoplay; encrypted-media; picture-in-picture" allowfullscreen></iframe></div>
        @elseif($resource->type === 'video')
            <video class="mx-auto max-h-[75vh] w-full rounded-xl bg-black" controls preload="metadata" src="{{ $resource->external_url ?: route('cfdt.resources.file', [$course, $resource]) }}"></video>
        @elseif($resource->type === 'epub')
            <div class="py-12 text-center"><p class="text-xl font-black">Livre numérique EPUB</p><p class="mt-2 dgcpt-text-muted">Ouvrez le livre dans le lecteur EPUB de votre appareil, puis revenez valider votre lecture.</p><a class="dgcpt-btn-primary mt-5 inline-flex" href="{{ route('cfdt.resources.file', [$course, $resource]) }}" target="_blank">Ouvrir ou télécharger le livre</a></div>
        @endif
    </section>
    @if($enrollment)
        @if($progress?->completed_at)<div class="rounded-xl border border-emerald-500/50 p-4 text-emerald-300">Support terminé le {{ $progress->completed_at->format('d/m/Y à H:i') }}.</div>
        @else<form method="post" action="{{ route('cfdt.resources.complete', [$course, $resource]) }}">@csrf @method('PATCH')<button class="dgcpt-btn-primary">J’ai terminé ce support</button></form>@endif
    @endif
</div>
</x-app-layout>
