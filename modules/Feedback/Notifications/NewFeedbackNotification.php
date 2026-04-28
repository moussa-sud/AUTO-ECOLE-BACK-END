<?php

namespace Modules\Feedback\Notifications;

use App\Models\User;
use Illuminate\Notifications\Notification;
use Modules\Feedback\Models\Feedback;

class NewFeedbackNotification extends Notification
{
    private const RATING_LABELS = [
        1 => 'ضعيف',
        2 => 'مقبول',
        3 => 'جيد',
        4 => 'جيد جداً',
        5 => 'ممتاز',
    ];

    public function __construct(
        private readonly Feedback $feedback,
        private readonly User     $student,
        private readonly User     $staff
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'         => 'new_feedback',
            'title'        => 'تقييم جديد من طالب',
            'message'      => "{$this->student->name} قيّم {$this->staff->name}",
            'feedback_id'  => $this->feedback->id,
            'student_id'   => $this->student->id,
            'student_name' => $this->student->name,
            'staff_id'     => $this->staff->id,
            'staff_name'   => $this->staff->name,
            'rating'       => $this->feedback->rating,
            'rating_label' => self::RATING_LABELS[$this->feedback->rating] ?? '',
            'comment'      => $this->feedback->comment,
        ];
    }
}
