<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-8 px-0 py-2">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="dgcpt-card-title">Organigramme institutionnel</p>
                <h1 class="dgcpt-page-title">Direction générale et structures rattachées</h1>
                <p class="mt-1 text-sm dgcpt-text-muted">Vue hiérarchique commune générée depuis les rattachements des structures.</p>
            </div>
            <a href="{{ route('admin.departments.index') }}" class="dgcpt-btn-outline">Retour structures</a>
        </div>

        <div class="dgcpt-surface p-6">
            <div class="grid gap-5">
                @foreach ($departmentTree as $root)
                    @include('iam.admin.departments.partials.org-node', ['department' => $root, 'level' => 0])
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
