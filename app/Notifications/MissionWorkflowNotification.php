<?php

namespace App\Notifications;

use App\Models\Mission;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class MissionWorkflowNotification extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        protected Mission $mission,
        protected string $action,
        protected ?string $comment,
        protected User $actor,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('[Audit DGCPT] '.$this->label().' — '.$this->mission->organisation)
            ->greeting('Bonjour '.$notifiable->displayName().',')
            ->line($this->bodyLine())
            ->action('Ouvrir la mission', route('missions.show', $this->mission, true));
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'mission_id' => $this->mission->id,
            'organisation' => $this->mission->organisation,
            'mission_status' => $this->mission->mission_status,
            'action' => $this->action,
            'actor_name' => $this->actor->displayName(),
            'comment' => $this->comment,
            'title' => 'Mission — '.$this->label(),
            'body' => $this->bodyLine(),
        ];
    }

    protected function label(): string
    {
        return match ($this->action) {
            'demarrer' => 'Passage en cours',
            'cloturer' => 'Clôture',
            'valider_is' => 'Validation Inspection des Services',
            'demander_corrections' => 'Demande de corrections',
            'valider_copri' => 'Validation COPRI',
            'renvoyer_copri' => 'Renvoi stratégique COPRI',
            default => $this->action,
        };
    }

    protected function bodyLine(): string
    {
        $base = $this->label().' — '.$this->mission->organisation.'.';
        if ($this->comment) {
            return $base.' Commentaire : '.$this->comment;
        }

        return $base;
    }
}
