<?php

namespace Modules\Results\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'user'             => $this->whenLoaded('user', fn() => [
                'id'    => $this->user->id,
                'name'  => $this->user->name,
                'email' => $this->user->email,
            ]),
            'series'           => $this->whenLoaded('series', fn() => [
                'id'    => $this->series->id,
                'title' => $this->series->title,
            ]),
            'score'            => $this->score,
            'total_questions'  => $this->total_questions,
            'correct_answers'  => $this->correct_answers,
            'percentage'       => $this->percentage,
            'attempt_number'   => $this->attempt_number,
            'answers_snapshot' => $this->answers_snapshot,
            'completed_at'     => $this->completed_at,
            'created_at'       => $this->created_at,
        ];
    }
}
