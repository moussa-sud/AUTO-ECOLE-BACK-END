<?php

namespace Modules\ExamRequests\Notifications;

use Illuminate\Notifications\Notification;
use Modules\ExamRequests\Models\ExamRequest;

class ExamRequestDecisionNotification extends Notification
{
    public function __construct(private readonly ExamRequest $examRequest) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $decision = $this->examRequest->owner_decision;
        $message  = $decision === 'approved'
            ? 'Congratulations! Your request to take the final exam has been approved.'
            : 'Your request to take the final exam has been rejected. ' . ($this->examRequest->owner_notes ?? '');

        return [
            'title'           => 'Exam Request ' . ucfirst($decision),
            'message'         => $message,
            'exam_request_id' => $this->examRequest->id,
            'decision'        => $decision,
        ];
    }
}
