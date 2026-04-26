<?php

namespace Modules\Feedback\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Feedback\Http\Resources\FeedbackResource;
use Modules\Feedback\Models\Feedback;

class FeedbackController extends Controller
{
    /**
     * GET /api/staff
     * Returns all active managers for the tenant so the student can select whom to rate.
     */
    public function staff(Request $request): JsonResponse
    {
        $staff = User::where('tenant_id', $request->user()->tenant_id)
            ->where('role', 'manager')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'avatar']);

        // Also include owners
        $owners = User::where('tenant_id', $request->user()->tenant_id)
            ->where('role', 'owner')
            ->orderBy('name')
            ->get(['id', 'name', 'avatar']);

        $all = $staff->merge($owners)->map(fn($u) => [
            'id'     => $u->id,
            'name'   => $u->name,
            'avatar' => $u->avatar ? url($u->avatar) : null,
        ]);

        return response()->json(['data' => $all], 200);
    }

    /**
     * GET /api/feedback/my
     * Returns the authenticated student's own submitted feedback (so the form can pre-fill).
     */
    public function myFeedback(Request $request): JsonResponse
    {
        $feedback = Feedback::where('tenant_id', $request->user()->tenant_id)
            ->where('student_id', $request->user()->id)
            ->with('staff')
            ->get();

        return response()->json(['data' => FeedbackResource::collection($feedback)], 200);
    }

    /**
     * POST /api/feedback
     * Student submits or updates feedback for a staff member.
     * One review per student per staff — uses updateOrCreate.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'staff_id' => 'required|integer|exists:users,id',
            'rating'   => 'required|integer|min:1|max:5',
            'comment'  => 'nullable|string|max:1000',
        ]);

        // Ensure target is manager/owner in same tenant
        $staff = User::where('id', $request->staff_id)
            ->where('tenant_id', $request->user()->tenant_id)
            ->whereIn('role', ['manager', 'owner'])
            ->firstOrFail();

        $feedback = Feedback::updateOrCreate(
            [
                'tenant_id'  => $request->user()->tenant_id,
                'student_id' => $request->user()->id,
                'staff_id'   => $staff->id,
            ],
            [
                'rating'  => $request->rating,
                'comment' => $request->comment,
            ]
        );

        return response()->json([
            'message' => 'تم حفظ تقييمك بنجاح.',
            'data'    => new FeedbackResource($feedback->load('staff', 'student')),
        ], 200);
    }

    /**
     * GET /api/feedback
     * Owner views all feedback — grouped by staff member with aggregate stats.
     */
    public function index(Request $request): JsonResponse
    {
        // Get all staff for this tenant (managers + owner themselves excluded from their own results)
        $staff = User::where('tenant_id', $request->user()->tenant_id)
            ->whereIn('role', ['manager', 'owner'])
            ->orderBy('name')
            ->get();

        $result = $staff->map(function ($member) use ($request) {
            $feedbacks = Feedback::where('tenant_id', $request->user()->tenant_id)
                ->where('staff_id', $member->id)
                ->with('student')
                ->latest()
                ->get();

            $count   = $feedbacks->count();
            $avg     = $count > 0 ? round($feedbacks->avg('rating'), 1) : null;
            $dist    = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
            foreach ($feedbacks as $f) {
                $dist[$f->rating] = ($dist[$f->rating] ?? 0) + 1;
            }

            return [
                'staff'          => [
                    'id'     => $member->id,
                    'name'   => $member->name,
                    'role'   => $member->role,
                    'avatar' => $member->avatar ? url($member->avatar) : null,
                ],
                'avg_rating'     => $avg,
                'review_count'   => $count,
                'distribution'   => $dist,
                'latest_reviews' => FeedbackResource::collection($feedbacks->take(5))->resolve(),
            ];
        });

        return response()->json(['data' => $result], 200);
    }

    /**
     * GET /api/feedback/staff/{staff}
     * Owner drills into all feedback for a specific staff member (paginated).
     */
    public function staffFeedback(Request $request, User $staff): JsonResponse
    {
        // Ensure staff belongs to same tenant
        if ($staff->tenant_id !== $request->user()->tenant_id) {
            abort(404);
        }

        $feedbacks = Feedback::where('tenant_id', $request->user()->tenant_id)
            ->where('staff_id', $staff->id)
            ->with('student')
            ->latest()
            ->paginate(20);

        return response()->json([
            'staff' => [
                'id'     => $staff->id,
                'name'   => $staff->name,
                'role'   => $staff->role,
                'avatar' => $staff->avatar ? url($staff->avatar) : null,
            ],
            'avg_rating'   => $feedbacks->avg('rating') ? round(Feedback::where('tenant_id', $request->user()->tenant_id)->where('staff_id', $staff->id)->avg('rating'), 1) : null,
            'review_count' => Feedback::where('tenant_id', $request->user()->tenant_id)->where('staff_id', $staff->id)->count(),
            'data'         => FeedbackResource::collection($feedbacks->items()),
            'meta'         => [
                'current_page' => $feedbacks->currentPage(),
                'last_page'    => $feedbacks->lastPage(),
                'total'        => $feedbacks->total(),
            ],
        ], 200);
    }
}
