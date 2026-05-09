<?php

namespace App\Notifications\Iam;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notification après plusieurs échecs de connexion (sans verrouillage définitif).
 */
class SuspiciousLoginAttemptNotification extends Notification
{
    use Queueable;

    public function __construct(
        public int $attemptCount,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Activité de connexion inhabituelle',
            'body' => $this->attemptCount.' tentative(s) de connexion infructueuse(s) détectée(s) sur votre compte.',
            'severity' => 'warning',
        ];
    }
}
