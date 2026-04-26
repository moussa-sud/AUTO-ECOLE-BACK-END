<?php

namespace Modules\Students\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\Students\Http\Resources\StudentResource;
use Modules\Students\Http\Resources\StudentResourceCollection;

class StudentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;

        $students = User::where('tenant_id', $tenantId)
            ->where('role', 'student')
            ->withCount(['results', 'examRequests'])
            ->latest()
            ->paginate(15);

        return response()->json([
            'data' => StudentResource::collection($students->items()),
            'meta' => [
                'current_page' => $students->currentPage(),
                'last_page'    => $students->lastPage(),
                'per_page'     => $students->perPage(),
                'total'        => $students->total(),
            ],
        ], 200);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'phone'    => 'nullable|string|max:20',
        ]);

        $student = User::create([
            'tenant_id' => $request->user()->tenant_id,
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => $request->password,
            'role'      => 'student',
            'phone'     => $request->phone,
        ]);

        return response()->json([
            'message' => 'Student created successfully.',
            'data'    => new StudentResource($student),
        ], 201);
    }

    public function show(Request $request, User $student): JsonResponse
    {
        $this->ensureSameTenant($request, $student);

        return response()->json([
            'data' => new StudentResource($student->loadCount(['results', 'examRequests'])),
        ], 200);
    }

    public function update(Request $request, User $student): JsonResponse
    {
        $this->ensureSameTenant($request, $student);

        $request->validate([
            'name'  => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
            'email' => 'sometimes|email|unique:users,email,' . $student->id,
        ]);

        $student->update($request->only('name', 'email', 'phone'));

        return response()->json([
            'message' => 'Student updated successfully.',
            'data'    => new StudentResource($student->fresh()),
        ], 200);
    }

    public function uploadAvatar(Request $request, User $student): JsonResponse
    {
        $this->ensureSameTenant($request, $student);

        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        // Delete old avatar if it exists
        if ($student->avatar) {
            $oldPath = str_replace('/storage/', '', $student->avatar);
            Storage::disk('public')->delete($oldPath);
        }

        $path = $request->file('avatar')->store('avatars', 'public');
        $url  = '/storage/' . $path;

        $student->update(['avatar' => $url]);

        return response()->json([
            'message' => 'Avatar updated successfully.',
            'data'    => new StudentResource($student->fresh()),
        ], 200);
    }

    public function removeAvatar(Request $request, User $student): JsonResponse
    {
        $this->ensureSameTenant($request, $student);

        if ($student->avatar) {
            $oldPath = str_replace('/storage/', '', $student->avatar);
            Storage::disk('public')->delete($oldPath);
            $student->update(['avatar' => null]);
        }

        return response()->json([
            'message' => 'Avatar removed.',
            'data'    => new StudentResource($student->fresh()),
        ], 200);
    }

    // ── Student self-profile ────────────────────────────────────────

    public function myProfile(Request $request): JsonResponse
    {
        return response()->json([
            'data' => new StudentResource($request->user()),
        ], 200);
    }

    public function updateMyProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'name'  => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
        ]);

        $user->update($request->only('name', 'phone'));

        return response()->json([
            'message' => 'Profile updated successfully.',
            'data'    => new StudentResource($user->fresh()),
        ], 200);
    }

    public function uploadMyAvatar(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        if ($user->avatar) {
            $oldPath = str_replace('/storage/', '', $user->avatar);
            Storage::disk('public')->delete($oldPath);
        }

        $path = $request->file('avatar')->store('avatars', 'public');
        $url  = '/storage/' . $path;

        $user->update(['avatar' => $url]);

        return response()->json([
            'message' => 'Avatar updated successfully.',
            'data'    => new StudentResource($user->fresh()),
        ], 200);
    }

    public function destroy(Request $request, User $student): JsonResponse
    {
        $this->ensureSameTenant($request, $student);

        $student->delete();

        return response()->json(['message' => 'Student deleted successfully.'], 200);
    }

    public function toggleStatus(Request $request, User $student): JsonResponse
    {
        $this->ensureSameTenant($request, $student);

        $student->update(['is_active' => !$student->is_active]);

        return response()->json([
            'message' => 'Student status updated.',
            'data'    => new StudentResource($student->fresh()),
        ], 200);
    }

    public function progress(Request $request, User $student): JsonResponse
    {
        $this->ensureSameTenant($request, $student);

        // Series progress with questions_count on each series
        $progress = $student->progress()
            ->with(['series' => fn($q) => $q->withCount('questions')])
            ->get();

        $totalSeries = \Modules\Series\Models\Series::where('tenant_id', $request->user()->tenant_id)
            ->where('is_active', true)
            ->count();

        $completedSeries  = $progress->where('quiz_completed', true)->count();
        $videosWatched    = $progress->where('video_watched', true)->count();
        $bestScore        = $progress->max('best_score') ?? 0;

        // Exam results for this student
        $results = \Modules\Results\Models\Result::where('user_id', $student->id)
            ->where('tenant_id', $request->user()->tenant_id)
            ->with('series')
            ->latest('completed_at')
            ->get()
            ->map(fn($r) => [
                'id'              => $r->id,
                'series_title'    => $r->series?->title,
                'score'           => $r->score,
                'total_questions' => $r->total_questions,
                'percentage'      => $r->percentage,
                'attempt_number'  => $r->attempt_number,
                'completed_at'    => $r->completed_at?->toISOString(),
            ]);

        return response()->json([
            'data' => [
                'student'          => new StudentResource($student),
                'total_series'     => $totalSeries,
                'completed_series' => $completedSeries,
                'videos_watched'   => $videosWatched,
                'best_score'       => $bestScore,
                'progress_percent' => $totalSeries > 0 ? round(($completedSeries / $totalSeries) * 100, 2) : 0,
                'series_progress'  => $progress,
                'exam_results'     => $results,
            ],
        ], 200);
    }

    private function ensureSameTenant(Request $request, User $student): void
    {
        if ($student->tenant_id !== $request->user()->tenant_id || $student->role !== 'student') {
            abort(404, 'Student not found.');
        }
    }
}
