<?php

namespace Modules\Dashboard\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Evaluations\Models\StudentEvaluation;
use Modules\ExamRequests\Models\ExamRequest;
use Modules\Results\Models\Result;
use Modules\Series\Models\Series;
use Modules\Series\Models\StudentSeriesProgress;

class DashboardController extends Controller
{
    public function ownerDashboard(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;

        $totalStudents  = User::where('tenant_id', $tenantId)->where('role', 'student')->count();
        $totalManagers  = User::where('tenant_id', $tenantId)->where('role', 'manager')->count();
        $totalSeries    = Series::where('tenant_id', $tenantId)->where('is_active', true)->count();
        $totalResults   = Result::where('tenant_id', $tenantId)->count();
        $pendingRequests = ExamRequest::where('tenant_id', $tenantId)
            ->whereIn('status', ['pending', 'manager_reviewed'])
            ->count();

        $avgScore = Result::where('tenant_id', $tenantId)->avg('percentage') ?? 0;

        // Pass rate (>= 70%)
        $passedResults = Result::where('tenant_id', $tenantId)->where('percentage', '>=', 70)->count();
        $passRate = $totalResults > 0 ? round(($passedResults / $totalResults) * 100, 2) : 0;

        // Recent students
        $recentStudents = User::where('tenant_id', $tenantId)
            ->where('role', 'student')
            ->latest()
            ->take(5)
            ->get(['id', 'name', 'email', 'created_at']);

        // Recent exam requests
        $recentRequests = ExamRequest::where('tenant_id', $tenantId)
            ->with('student:id,name,email')
            ->latest()
            ->take(5)
            ->get();

        // Pending exam requests with full student details
        $pendingWithDetails = $this->buildPendingRequestsDetails($tenantId, ['pending', 'manager_reviewed']);

        return response()->json([
            'data' => [
                'stats' => [
                    'total_students'   => $totalStudents,
                    'total_managers'   => $totalManagers,
                    'total_series'     => $totalSeries,
                    'total_results'    => $totalResults,
                    'pending_requests' => $pendingRequests,
                    'avg_score'        => round($avgScore, 2),
                    'pass_rate'        => $passRate,
                ],
                'recent_students'            => $recentStudents,
                'recent_requests'            => $recentRequests,
                'pending_requests_details'   => $pendingWithDetails,
            ],
        ], 200);
    }

    public function managerDashboard(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;

        $totalStudents   = User::where('tenant_id', $tenantId)->where('role', 'student')->count();
        $pendingRequests = ExamRequest::where('tenant_id', $tenantId)->where('status', 'pending')->count();

        $studentsWithProgress = StudentSeriesProgress::where('tenant_id', $tenantId)
            ->where('quiz_completed', true)
            ->distinct('user_id')
            ->count('user_id');

        $recentResults = Result::where('tenant_id', $tenantId)
            ->with(['user:id,name', 'series:id,title'])
            ->latest('completed_at')
            ->take(10)
            ->get();

        // Pending exam requests with full student details
        $pendingWithDetails = $this->buildPendingRequestsDetails($tenantId, ['pending']);

        return response()->json([
            'data' => [
                'stats' => [
                    'total_students'          => $totalStudents,
                    'pending_requests'        => $pendingRequests,
                    'students_with_progress'  => $studentsWithProgress,
                ],
                'recent_results'             => $recentResults,
                'pending_requests_details'   => $pendingWithDetails,
            ],
        ], 200);
    }

    public function studentDashboard(Request $request): JsonResponse
    {
        $userId   = $request->user()->id;
        $tenantId = $request->user()->tenant_id;

        $totalSeries   = Series::where('tenant_id', $tenantId)->where('is_active', true)->count();
        $myProgress    = StudentSeriesProgress::where('user_id', $userId)->get();
        $completedCount = $myProgress->where('quiz_completed', true)->count();
        $watchedCount   = $myProgress->where('video_watched', true)->count();

        $myResults   = Result::where('user_id', $userId)->latest('completed_at')->take(5)->with('series:id,title')->get();
        $avgScore    = Result::where('user_id', $userId)->avg('percentage') ?? 0;
        $examRequest = ExamRequest::where('user_id', $userId)->latest()->first();

        $evalModel   = StudentEvaluation::where('student_id', $userId)->with('evaluatedBy:id,name')->latest()->first();
        $evaluation  = $evalModel ? [
            'average'            => $evalModel->average_score,
            'final_status'       => $evalModel->final_status,
            'parking_score'      => $evalModel->parking_score,
            'reverse_score'      => $evalModel->reverse_score,
            'city_driving_score' => $evalModel->city_driving_score,
            'steering_score'     => $evalModel->steering_score,
            'rules_score'        => $evalModel->rules_score,
            'confidence_score'   => $evalModel->confidence_score,
            'comment'            => $evalModel->comment,
            'evaluated_by_name'  => $evalModel->evaluatedBy?->name,
        ] : null;

        return response()->json([
            'data' => [
                'stats' => [
                    'total_series'     => $totalSeries,
                    'completed_series' => $completedCount,
                    'videos_watched'   => $watchedCount,
                    'progress_percent' => $totalSeries > 0 ? round(($completedCount / $totalSeries) * 100, 2) : 0,
                    'avg_score'        => round($avgScore, 2),
                ],
                'recent_results'  => $myResults,
                'exam_request'    => $examRequest,
                'evaluation'      => $evaluation,
            ],
        ], 200);
    }

    private function buildPendingRequestsDetails(int $tenantId, array $statuses): array
    {
        $requests = ExamRequest::where('tenant_id', $tenantId)
            ->whereIn('status', $statuses)
            ->with('student:id,name,email,created_at')
            ->latest()
            ->take(10)
            ->get();

        $totalSeries = Series::where('tenant_id', $tenantId)->where('is_active', true)->count();

        return $requests->map(function ($req) use ($totalSeries) {
            $student = $req->student;
            if (!$student) return null;

            $results = Result::where('user_id', $student->id)->get();
            $avgScore = round($results->avg('percentage') ?? 0, 1);
            $bestScore = round($results->max('percentage') ?? 0, 1);
            $quizCount = $results->count();

            $completedSeries = StudentSeriesProgress::where('user_id', $student->id)
                ->where('quiz_completed', true)->count();
            $progressPct = $totalSeries > 0 ? round(($completedSeries / $totalSeries) * 100) : 0;

            $seriesScores = Result::where('user_id', $student->id)
                ->with('series:id,title')
                ->selectRaw('series_id, MAX(percentage) as best_pct, COUNT(*) as attempts')
                ->groupBy('series_id')
                ->get()
                ->map(fn($r) => [
                    'series_title' => $r->series?->title,
                    'best_pct'     => round($r->best_pct, 1),
                    'attempts'     => $r->attempts,
                ]);

            return [
                'exam_request_id'  => $req->id,
                'status'           => $req->status,
                'submitted_at'     => $req->created_at,
                'student' => [
                    'id'    => $student->id,
                    'name'  => $student->name,
                    'email' => $student->email,
                ],
                'scores' => [
                    'avg_score'        => $avgScore,
                    'best_score'       => $bestScore,
                    'quiz_count'       => $quizCount,
                    'completed_series' => $completedSeries,
                    'total_series'     => $totalSeries,
                    'progress_pct'     => $progressPct,
                    'series_breakdown' => $seriesScores,
                ],
            ];
        })->filter()->values()->toArray();
    }

    public function notifications(Request $request): JsonResponse
    {
        $notifications = $request->user()->notifications()->latest()->take(20)->get();

        return response()->json([
            'data'          => $notifications,
            'unread_count'  => $request->user()->unreadNotifications()->count(),
        ], 200);
    }

    public function markNotificationRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->find($id);
        if ($notification) {
            $notification->markAsRead();
        }

        return response()->json(['message' => 'Notification marked as read.'], 200);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['message' => 'All notifications marked as read.'], 200);
    }
}
