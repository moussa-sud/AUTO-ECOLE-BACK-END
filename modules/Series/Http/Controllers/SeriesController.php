<?php

namespace Modules\Series\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Series\Http\Resources\SeriesResource;
use Modules\Series\Models\Answer;
use Modules\Series\Models\Question;
use Modules\Series\Models\Series;
use Modules\Series\Models\StudentSeriesProgress;

class SeriesController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $userId   = $request->user()->id;
        $isStudent = $request->user()->isStudent();

        $series = Series::where('tenant_id', $tenantId)
            ->when($isStudent, fn($q) => $q->where('is_active', true))
            ->withCount('questions')
            ->orderBy('order')
            ->get();

        // Attach student progress if the user is a student
        if ($isStudent) {
            $progress = StudentSeriesProgress::where('user_id', $userId)
                ->whereIn('series_id', $series->pluck('id'))
                ->get()
                ->keyBy('series_id');

            $series->each(function ($s) use ($progress) {
                $s->student_progress = $progress->get($s->id);
            });
        }

        return response()->json(['data' => SeriesResource::collection($series)], 200);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'thumbnail'   => 'nullable|string|url',
            'order'       => 'nullable|integer|min:0',
        ]);

        $series = Series::create([
            'tenant_id'   => $request->user()->tenant_id,
            'title'       => $request->title,
            'description' => $request->description,
            'thumbnail'   => $request->thumbnail,
            'order'       => $request->order ?? 0,
        ]);

        return response()->json([
            'message' => 'Series created successfully.',
            'data'    => new SeriesResource($series),
        ], 201);
    }

    public function show(Request $request, Series $series): JsonResponse
    {
        $this->ensureSameTenant($request, $series);

        $series->load(['videos', 'questions.answers']);

        // Attach student progress
        if ($request->user()->isStudent()) {
            $series->student_progress = StudentSeriesProgress::where('user_id', $request->user()->id)
                ->where('series_id', $series->id)
                ->first();
        }

        return response()->json(['data' => new SeriesResource($series)], 200);
    }

    public function update(Request $request, Series $series): JsonResponse
    {
        $this->ensureSameTenant($request, $series);

        $request->validate([
            'title'       => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'thumbnail'   => 'nullable|string|url',
            'order'       => 'nullable|integer|min:0',
            'is_active'   => 'nullable|boolean',
        ]);

        $series->update($request->only('title', 'description', 'thumbnail', 'order', 'is_active'));

        return response()->json([
            'message' => 'Series updated successfully.',
            'data'    => new SeriesResource($series->fresh()),
        ], 200);
    }

    public function destroy(Request $request, Series $series): JsonResponse
    {
        $this->ensureSameTenant($request, $series);
        $series->delete();

        return response()->json(['message' => 'Series deleted successfully.'], 200);
    }

    public function addQuestion(Request $request, Series $series): JsonResponse
    {
        $this->ensureSameTenant($request, $series);

        $request->validate([
            'question_text'       => 'required|string',
            'image'               => 'nullable|string',
            'order'               => 'nullable|integer',
            'answers'             => 'required|array|min:2',
            'answers.*.text'      => 'required|string',
            'answers.*.is_correct' => 'required|boolean',
        ]);

        // Ensure exactly one correct answer
        $correctCount = collect($request->answers)->where('is_correct', true)->count();
        if ($correctCount !== 1) {
            return response()->json(['message' => 'Exactly one answer must be marked as correct.'], 422);
        }

        $question = Question::create([
            'series_id'     => $series->id,
            'question_text' => $request->question_text,
            'image'         => $request->image,
            'order'         => $request->order ?? 0,
        ]);

        foreach ($request->answers as $answerData) {
            Answer::create([
                'question_id' => $question->id,
                'text'        => $answerData['text'],
                'is_correct'  => $answerData['is_correct'],
            ]);
        }

        return response()->json([
            'message' => 'Question added successfully.',
            'data'    => $question->load('answers'),
        ], 201);
    }

    public function updateQuestion(Request $request, Series $series, Question $question): JsonResponse
    {
        $this->ensureSameTenant($request, $series);

        if ($question->series_id !== $series->id) {
            abort(404, 'Question not found in this series.');
        }

        $request->validate([
            'question_text'        => 'sometimes|string',
            'image'                => 'nullable|string',
            'order'                => 'nullable|integer',
            'answers'              => 'sometimes|array|min:2',
            'answers.*.text'       => 'required_with:answers|string',
            'answers.*.is_correct' => 'required_with:answers|boolean',
        ]);

        $question->update($request->only('question_text', 'image', 'order'));

        if ($request->has('answers')) {
            $correctCount = collect($request->answers)->where('is_correct', true)->count();
            if ($correctCount !== 1) {
                return response()->json(['message' => 'Exactly one answer must be marked as correct.'], 422);
            }
            $question->answers()->delete();
            foreach ($request->answers as $answerData) {
                Answer::create([
                    'question_id' => $question->id,
                    'text'        => $answerData['text'],
                    'is_correct'  => $answerData['is_correct'],
                ]);
            }
        }

        return response()->json([
            'message' => 'Question updated successfully.',
            'data'    => $question->fresh()->load('answers'),
        ], 200);
    }

    public function deleteQuestion(Request $request, Series $series, Question $question): JsonResponse
    {
        $this->ensureSameTenant($request, $series);

        if ($question->series_id !== $series->id) {
            abort(404, 'Question not found in this series.');
        }

        $question->delete();

        return response()->json(['message' => 'Question deleted successfully.'], 200);
    }

    /**
     * GET /api/series/{series}/questions
     * Returns questions with answers. correct_answer_id is always included
     * (frontend hides it in exam mode; API trusts the client for UX only).
     */
    public function questions(Request $request, Series $series): JsonResponse
    {
        $this->ensureSameTenant($request, $series);

        $questions = $series->questions()
            ->with('answers')
            ->orderBy('order')
            ->get()
            ->map(function ($question) {
                return [
                    'id'            => $question->id,
                    'question_text' => $question->question_text,
                    'image'         => $question->image,
                    'order'         => $question->order,
                    'answers'       => $question->answers->map(fn($a) => [
                        'id'         => $a->id,
                        'text'       => $a->text,
                        'is_correct' => $a->is_correct,
                    ]),
                    'correct_answer_id' => $question->answers->firstWhere('is_correct', true)?->id,
                ];
            });

        return response()->json([
            'data'  => $questions,
            'total' => $questions->count(),
        ]);
    }

    public function markVideoWatched(Request $request, Series $series): JsonResponse
    {
        $this->ensureSameTenant($request, $series);

        StudentSeriesProgress::updateOrCreate(
            ['user_id' => $request->user()->id, 'series_id' => $series->id],
            [
                'tenant_id'       => $request->user()->tenant_id,
                'video_watched'   => true,
                'video_watched_at' => now(),
            ]
        );

        return response()->json(['message' => 'Video marked as watched.'], 200);
    }

    private function ensureSameTenant(Request $request, Series $series): void
    {
        if ($series->tenant_id !== $request->user()->tenant_id) {
            abort(404, 'Series not found.');
        }
    }
}
