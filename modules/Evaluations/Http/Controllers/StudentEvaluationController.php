<?php

namespace Modules\Evaluations\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Evaluations\Http\Resources\StudentEvaluationResource;
use Modules\Evaluations\Models\StudentEvaluation;

class StudentEvaluationController extends Controller
{
    /**
     * GET /api/students/{studentId}/evaluation
     *
     * Owner / Manager : can fetch any student's evaluation in their tenant.
     * Student         : can only fetch their own evaluation.
     */
    public function show(Request $request, int $studentId): JsonResponse
    {
        $user     = $request->user();
        $tenantId = $user->tenant_id;

        if ($user->role === 'student' && $user->id !== $studentId) {
            return response()->json(['message' => 'غير مصرح.'], 403);
        }

        $evaluation = StudentEvaluation::where('tenant_id', $tenantId)
            ->where('student_id', $studentId)
            ->with('evaluatedBy')
            ->first();

        if (! $evaluation) {
            return response()->json(null);
        }

        return response()->json(new StudentEvaluationResource($evaluation));
    }

    /**
     * POST /api/students/{studentId}/evaluation
     *
     * Creates or updates (upsert) the student's evaluation.
     * Only Owner and Manager are allowed.
     */
    public function store(Request $request, int $studentId): JsonResponse
    {
        $user = $request->user();

        if (! in_array($user->role, ['owner', 'manager'])) {
            return response()->json(['message' => 'غير مصرح.'], 403);
        }

        $data = $request->validate([
            'parking_score'      => 'nullable|integer|min:1|max:5',
            'reverse_score'      => 'nullable|integer|min:1|max:5',
            'city_driving_score' => 'nullable|integer|min:1|max:5',
            'steering_score'     => 'nullable|integer|min:1|max:5',
            'rules_score'        => 'nullable|integer|min:1|max:5',
            'confidence_score'   => 'nullable|integer|min:1|max:5',
            'comment'            => 'nullable|string|max:2000',
            'final_status'       => 'nullable|in:ready,not_ready',
        ]);

        $evaluation = StudentEvaluation::updateOrCreate(
            [
                'tenant_id'  => $user->tenant_id,
                'student_id' => $studentId,
            ],
            array_merge($data, ['evaluated_by' => $user->id])
        );

        return response()->json(
            new StudentEvaluationResource($evaluation->load('evaluatedBy')),
            201
        );
    }
}
