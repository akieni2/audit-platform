<?php

namespace App\Notifications;

use App\Models\User;
use App\Models\WorkflowInstance;
use App\Models\WorkflowStage;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class WorkflowRuntimeNotification extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        protected WorkflowInstance $instance,
        protected ?WorkflowStage $stage,
        protected string $eventName,
        protected string $title,
        protected string $body,
        protected ?User $actor = null,
        protected array $payload = [],
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (config('broadcasting.default') !== 'null') {
            $channels[] = 'broadcast';
        }

        return $channels;
    }

    /**
     * @return array<int, \Illuminate\Broadcasting\PrivateChannel>
     */
    public function broadcastOn(): array
    {
        $auditeurId = $this->instance->mission?->auditeur_id;
        if ($auditeurId === null) {
            return [];
        }

        return [
            new PrivateChannel('App.Models.User.'.$auditeurId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'workflow.runtime';
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->payloadForStorage());
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return $this->payloadForStorage();
    }

    /**
     * @return array<string, mixed>
     */
    private function payloadForStorage(): array
    {
        return [
            'workflow_instance_id' => $this->instance->id,
            'mission_id' => $this->instance->mission_id,
            'workflow_stage_id' => $this->stage?->id,
            'workflow_stage_name' => $this->stage?->name,
            'event_name' => $this->eventName,
            'actor_name' => $this->actor?->displayName(),
            'title' => $this->title,
            'body' => $this->body,
            'payload' => $this->payload,
        ];
    }
}
