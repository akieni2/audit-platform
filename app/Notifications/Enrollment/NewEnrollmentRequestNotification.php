<?php

namespace App\Notifications\Enrollment;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewEnrollmentRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public User $applicant,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('admin.enrollments.index', absolute: true);

        return (new MailMessage)
            ->subject('[DGCPT] Nouvelle demande d\'enrôlement')
            ->greeting('Bonjour,')
            ->line('Une nouvelle demande d\'accès à la plateforme a été soumise.')
            ->line('Demandeur : '.$this->applicant->displayName().' ('.$this->applicant->email.')')
            ->action('Traiter les demandes', $url);
    }
}
