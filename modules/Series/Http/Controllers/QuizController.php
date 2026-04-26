<?php

namespace Modules\Series\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Results\Models\Result;
use Modules\Series\Models\Series;
use Modules\Series\Models\StudentSeriesProgress;

class QuizController extends Controller
{
    public function submit(Request $request, Series $series): JsonResponse
    {
        if ($series->tenant_id !== $request->user()->tenant_id) {
            abort(404, 'Series not found.');
        }

        $request->validate([
            'answers'              => 'required|array',
            'answers.*.question_id' => 'required|integer|exists:questions,id',
            'answers.*.answer_id'  => 'required|integer|exists:answers,id',
        ]);

        $questions = $series->questions()->with('answers')->get();

        if ($questions->isEmpty()) {
            return response()->json(['message' => 'This series has no questions.'], 422);
        }

        $correctAnswers = 0;
        $answersSnapshot = [];

        foreach ($request->answers as $submission) {
            $question = $questions->firstWhere('id', $submission['question_id']);
            if (!$question) continue;

            $selectedAnswer = $question->answers->firstWhere('id', $submission['answer_id']);
            $correctAnswer  = $question->answers->firstWhere('is_correct', true);

            $isCorrect = $selectedAnswer && $selectedAnswer->is_correct;
            if ($isCorrect) $correctAnswers++;

            $answersSnapshot[] = [
                'question_id'       => $question->id,
                'question_text'     => $question->question_text,
                'selected_answer_id' => $submission['answer_id'],
                'selected_answer'   => $selectedAnswer?->text,
                'correct_answer_id' => $correctAnswer?->id,
                'correct_answer'    => $correctAnswer?->text,
                'is_correct'        => $isCorrect,
            ];
        }

        $totalQuestions = $questions->count();
        $percentage     = $totalQuestions > 0 ? round(($correctAnswers / $totalQuestions) * 100, 2) : 0;

        // Get previous attempt count
        $progress = StudentSeriesProgress::where('user_id', $request->user()->id)
            ->where('series_id', $series->id)
            ->first();

        $attemptNumber = ($progress?->attempts_count ?? 0) + 1;

        // Save result
        $result = Result::create([
            'user_id'          => $request->user()->id,
            'series_id'        => $series->id,
            'tenant_id'        => $request->user()->tenant_id,
            'score'            => $correctAnswers,
            'total_questions'  => $totalQuestions,
            'correct_answers'  => $correctAnswers,
            'percentage'       => $percentage,
            'attempt_number'   => $attemptNumber,
            'answers_snapshot' => $answersSnapshot,
            'completed_at'     => now(),
        ]);

        // Update progress
        StudentSeriesProgress::updateOrCreate(
            ['user_id' => $request->user()->id, 'series_id' => $series->id],
            [
                'tenant_id'          => $request->user()->tenant_id,
                'quiz_completed'     => true,
                'quiz_completed_at'  => now(),
                'best_score'         => max($progress?->best_score ?? 0, $correctAnswers),
                'attempts_count'     => $attemptNumber,
            ]
        );

        return response()->json([
            'message'         => 'Quiz submitted successfully.',
            'data' => [
                'result_id'       => $result->id,
                'score'           => $correctAnswers,
                'total_questions' => $totalQuestions,
                'percentage'      => $percentage,
                'attempt_number'  => $attemptNumber,
                'answers_review'  => $answersSnapshot,
            ],
        ], 200);
    }
}
