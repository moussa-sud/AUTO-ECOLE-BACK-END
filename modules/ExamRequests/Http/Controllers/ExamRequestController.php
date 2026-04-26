<?php

namespace Modules\ExamRequests\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ExamRequests\Http\Resources\ExamRequestResource;
use Modules\ExamRequests\Models\ExamRequest;

class ExamRequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = ExamRequest::where('tenant_id', $user->tenant_id)
            ->with(['student', 'manager', 'owner'])
            ->latest();

        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $requests = $query->paginate(15);

        return response()->json([
            'data' => ExamRequestResource::collection($requests->items()),
            'meta' => [
                'current_page' => $requests->currentPage(),
                'last_page'    => $requests->lastPage(),
                'total'        => $requests->total(),
            ],
        ], 200);
    }

    public function store(Request $request): JsonResponse
    {
        // Check if student already has a pending request
        $existing = ExamRequest::where('user_id', $request->user()->id)
            ->whereIn('status', ['pending', 'manager_reviewed'])
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'You already have a pending exam request.',
            ], 422);
        }

        $examRequest = ExamRequest::create([
            'user_id'   => $request->user()->id,
            'tenant_id' => $request->user()->tenant_id,
            'status'    => 'pending',
        ]);

        // Notify owner + all managers
        $notification = new \Modules\ExamRequests\Notifications\NewExamRequestNotification($examRequest, $request->user());

        $recipients = \App\Models\User::where('tenant_id', $request->user()->tenant_id)
            ->whereIn('role', ['owner', 'manager'])
            ->get();

        foreach ($recipients as $recipient) {
            $recipient->notify($notification);
        }

        return response()->json([
            'message' => 'Exam request submitted successfully.',
            'data'    => new ExamRequestResource($examRequest->load('student')),
        ], 201);
    }

    public function myRequests(Request $request): JsonResponse
    {
        $requests = ExamRequest::where('user_id', $request->user()->id)
            ->with(['manager', 'owner'])
            ->latest()
            ->get();

        return response()->json(['data' => ExamRequestResource::collection($requests)], 200);
    }

    public function show(Request $request, ExamRequest $examRequest): JsonResponse
    {
        $this->ensureAccess($request, $examRequest);

        return response()->json([
            'data' => new ExamRequestResource($examRequest->load(['student', 'manager', 'owner'])),
        ], 200);
    }

    public function managerReview(Request $request, ExamRequest $examRequest): JsonResponse
    {
        if ($examRequest->tenant_id !== $request->user()->tenant_id) {
            abort(404);
        }

        if ($examRequest->status !== 'pending') {
            return response()->json(['message' => 'This request has already been reviewed.'], 422);
        }

        $request->validate([
            'recommendation' => 'required|in:approved,rejected',
            'notes'          => 'nullable|string|max:500',
        ]);

        $examRequest->update([
            'status'                  => 'manager_reviewed',
            'manager_id'              => $request->user()->id,
            'manager_recommendation'  => $request->recommendation,
            'manager_notes'           => $request->notes,
            'manager_reviewed_at'     => now(),
        ]);

        return response()->json([
            'message' => 'Review submitted successfully.',
            'data'    => new ExamRequestResource($examRequest->fresh()->load(['student', 'manager'])),
        ], 200);
    }

    public function ownerDecide(Request $request, ExamRequest $examRequest): JsonResponse
    {
        if ($examRequest->tenant_id !== $request->user()->tenant_id) {
            abort(404);
        }

        if (!in_array($examRequest->status, ['pending', 'manager_reviewed'])) {
            return response()->json(['message' => 'This request has already been decided.'], 422);
        }

        $request->validate([
            'decision'           => 'required|in:approved,rejected',
            'notes'              => 'nullable|string|max:500',
            'exam_duration_days' => 'required_if:decision,approved|nullable|integer|min:1|max:365',
        ]);

        $examDate = null;
        if ($request->decision === 'approved' && $request->exam_duration_days) {
            $examDate = now()->addDays((int) $request->exam_duration_days)->toDateString();
        }

        $examRequest->update([
            'status'             => $request->decision,
            'owner_id'           => $request->user()->id,
            'owner_decision'     => $request->decision,
            'owner_notes'        => $request->notes,
            'owner_decided_at'   => now(),
            'exam_duration_days' => $request->decision === 'approved' ? $request->exam_duration_days : null,
            'exam_date'          => $examDate,
        ]);

        // Notify student
        $examRequest->student->notify(
            new \Modules\ExamRequests\Notifications\ExamRequestDecisionNotification($examRequest)
        );

        return response()->json([
            'message' => 'Decision recorded successfully.',
            'data'    => new ExamRequestResource($examRequest->fresh()->load(['student', 'manager', 'owner'])),
        ], 200);
    }

    public function destroy(Request $request, ExamRequest $examRequest): JsonResponse
    {
        if ($examRequest->tenant_id !== $request->user()->tenant_id) {
            abort(404);
        }

        $examRequest->delete();

        return response()->json(['message' => 'Exam request deleted successfully.'], 200);
    }

    private function ensureAccess(Request $request, ExamRequest $examRequest): void
    {
        $user = $request->user();
        $allowed = $examRequest->tenant_id === $user->tenant_id &&
            ($user->isOwner() || $user->isManager() || $examRequest->user_id === $user->id);

        if (!$allowed) {
            abort(403, 'Unauthorized.');
        }
    }
}
