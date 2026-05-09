<?php

namespace App\Notifications;

use App\Models\Department;
use App\Models\Risque;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class RiskEscalatedTransversalNotification extends Notification
{
    public function __construct(
        public Risque $risque,
        public Department $targetDepartment,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'risk_transversal',
            'risque_id' => $this->risque->id,
            'department_code' => $this->targetDepartment->code,
            'message' => 'Risque transversal identifié et routé vers votre pôle : '
                .Str::limit((string) $this->risque->description, 120),
        ];
    }
}
