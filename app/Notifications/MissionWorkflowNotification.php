<?php

namespace App\Notifications;

use App\Models\Mission;
use App\Models\User;
use Illuminate\Notifications\Notification;

class MissionWorkflowNotification extends Notification
{
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
        return ['database'];
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
