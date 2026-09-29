<x-app-layout>
    <div class="mx-auto max-w-4xl space-y-6">
        <div>
            <p class="dgcpt-card-title">Services numériques DGCPT</p>
            <h1 class="dgcpt-page-title">Messagerie institutionnelle</h1>
            <p class="dgcpt-text-muted">Consultez et envoyez vos courriels depuis le Webmail sécurisé du Trésor public.</p>
        </div>

        <section class="dgcpt-surface overflow-hidden p-6 sm:p-8">
            <div class="grid gap-6 md:grid-cols-[1fr_auto] md:items-center">
                <div class="space-y-3">
                    <h2 class="text-xl font-bold text-[#E6EEF8]">Zimbra — Trésor public</h2>
                    <p class="text-sm leading-6 text-[#B8C6D9]">
                        La messagerie s’ouvre dans son interface officielle. Utilisez vos identifiants
                        <strong class="text-white">tresorpublic.ga</strong>. La plateforme DGCPT ne reçoit,
                        ne conserve et ne journalise jamais votre mot de passe de messagerie.
                    </p>
                    @if ($isMobileApp ?? false)
                        <p class="rounded-xl border border-[rgba(0,209,255,.22)] bg-[rgba(0,209,255,.08)] p-3 text-sm text-[#BFEFFF]">
                            Dans l’application Android, utilisez le bouton Retour du téléphone pour revenir à la plateforme.
                        </p>
                    @endif
                </div>

                <a
                    class="dgcpt-btn-primary inline-flex min-h-12 items-center justify-center text-center"
                    href="{{ $webmailUrl }}"
                    rel="noreferrer"
                    @unless($isMobileApp ?? false) target="_blank" @endunless
                >
                    Ouvrir ma messagerie
                </a>
            </div>
        </section>

        <section class="dgcpt-surface p-5">
            <h2 class="font-bold text-[#E6EEF8]">Conseils de sécurité</h2>
            <ul class="mt-3 list-disc space-y-2 pl-5 text-sm text-[#B8C6D9]">
                <li>Vérifiez que l’adresse commence toujours par <strong class="text-white">https://mail.tresorpublic.ga</strong>.</li>
                <li>Ne communiquez jamais votre mot de passe par courriel, téléphone ou messagerie instantanée.</li>
                <li>Sur un appareil partagé, déconnectez-vous de Zimbra après utilisation.</li>
            </ul>
        </section>
    </div>
</x-app-layout>
