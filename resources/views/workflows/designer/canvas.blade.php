@php
    $nodeMap = collect($canvas['nodes'])->keyBy('id')->all();
    $layoutWidth = data_get($canvas, 'layout.width', 1440);
    $layoutHeight = data_get($canvas, 'layout.height', 760);
@endphp

<div class="space-y-4">
    @include('workflows.designer.toolbar', ['canvas' => $canvas])

    <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr),300px]">
        <div class="overflow-hidden rounded-[2rem] border border-[rgba(0,209,255,0.12)] bg-[radial-gradient(circle_at_top,_rgba(10,42,102,0.55),_rgba(5,8,22,0.96))]">
            <div class="border-b border-[rgba(0,209,255,0.08)] px-5 py-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-bold text-[#E6EEF8]">Canvas workflow</h2>
                        <p class="mt-1 text-sm text-[#9FB3C8]">Drag/drop, zoom, minimap, transitions dynamiques et autosave layout.</p>
                    </div>
                    <a href="{{ route('workflow-builder.edit', $template) }}" class="text-sm font-semibold text-[#73D8FF] hover:underline">Réinitialiser sélection</a>
                </div>
            </div>

            <div id="workflow-canvas" class="relative overflow-auto p-6" data-layout-width="{{ $layoutWidth }}" data-layout-height="{{ $layoutHeight }}">
                <div id="workflow-canvas-stage" class="relative origin-top-left transition-transform duration-150" style="width: {{ $layoutWidth }}px; height: {{ $layoutHeight }}px;">
                    @foreach ($canvas['transitions'] as $edge)
                        @include('workflows.designer.edge', ['edge' => $edge, 'nodeMap' => $nodeMap])
                    @endforeach

                    @forelse ($canvas['nodes'] as $node)
                        @include('workflows.designer.node', ['node' => $node, 'template' => $template])
                    @empty
                        <div class="flex h-full items-center justify-center rounded-3xl border border-dashed border-[rgba(0,209,255,0.18)] bg-[rgba(5,8,22,0.45)] text-sm text-[#9FB3C8]">
                            Aucune étape pour ce workflow. Commencez par ajouter une première carte.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="space-y-4">
            @include('workflows.designer.minimap', ['canvas' => $canvas])
        </div>
    </div>
</div>
