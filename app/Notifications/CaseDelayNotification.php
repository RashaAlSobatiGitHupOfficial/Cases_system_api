<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class CaseDelayNotification extends Notification
{
    public function __construct(
        public $case,
        public string $type // warning | exceeded
    ) {}

    public function via($notifiable)
    {
        return ['database'];
    }

   public function toDatabase($notifiable)
    {
        $days = (int) $this->case->created_at->diffInDays(now());

        $message = $this->type === 'warning'
            ? "Case {$this->case->id} with title {$this->case->title} will exceed delay time soon (created {$this->case->created_at->diffForHumans()})."
            : "Case {$this->case->id} {$this->case->title} has exceeded delay time by {$days} days.";

        return [
            'case_id'  => $this->case->id,
            'type'     => $this->type,
            'message'  => $message,
            'priority' => $this->case->priority->priority_name,
        ];
    }

}
