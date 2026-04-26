<?php

namespace Modules\ExamRequests\Notifications;

use App\Models\User;
use Illuminate\Notifications\Notification;
use Modules\ExamRequests\Models\ExamRequest;

class NewExamRequestNotification extends Notification
{
    public function __construct(
        private readonly ExamRequest $examRequest,
        private readonly User $student
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        // Load student scores summary
        $results  = \Modules\Results\Models\Result::where('user_id', $this->student->id)->get();
        $avgScore = $results->avg('percentage') ?? 0;

        $totalSeries = \Modules\Series\Models\Series::where('tenant_id', $this->student->tenant_id)
            ->where('is_active', true)->count();

        $completedSeries = \Modules\Series\Models\StudentSeriesProgress::where('user_id', $this->student->id)
            ->where('quiz_completed', true)->count();

        $progressPct = $totalSeries > 0 ? round(($completedSeries / $totalSeries) * 100) : 0;

        return [
            'type'             => 'new_exam_request',
            'title'            => 'طلب امتحان جديد',
            'message'          => "{$this->student->name} طلب الإذن لاجتياز الامتحان النهائي.",
            'exam_request_id'  => $this->examRequest->id,
            'student_id'       => $this->student->id,
            'student_name'     => $this->student->name,
            'student_email'    => $this->student->email,
            'avg_score'        => round($avgScore, 1),
            'completed_series' => $completedSeries,
            'total_series'     => $totalSeries,
            'progress_pct'     => $progressPct,
        ];
    }
}
