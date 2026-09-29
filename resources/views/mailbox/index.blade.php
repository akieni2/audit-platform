<x-app-layout>
    <div class="mx-auto max-w-3xl space-y-6">
        <section class="dgcpt-surface p-8 text-center sm:p-12">
            <h1 class="dgcpt-page-title">Messagerie DGCPT</h1>
            <div class="mt-8">
                <a
                    class="dgcpt-btn-primary inline-flex min-h-12 items-center justify-center text-center"
                    href="{{ ($isMobileApp ?? false) ? 'dgcptmail://open' : $webmailUrl }}"
                    rel="noreferrer"
                    @unless($isMobileApp ?? false) target="_blank" @endunless
                >
                    Ouvrir ma messagerie
                </a>
            </div>
        </section>
    </div>
</x-app-layout>
