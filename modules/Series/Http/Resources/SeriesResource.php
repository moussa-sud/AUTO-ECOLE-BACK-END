<?php

namespace Modules\Series\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SeriesResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'tenant_id'        => $this->tenant_id,
            'title'            => $this->title,
            'description'      => $this->description,
            'thumbnail'        => $this->thumbnail,
            'order'            => $this->order,
            'is_active'        => $this->is_active,
            'questions_count'  => $this->whenCounted('questions'),
            'videos'           => $this->whenLoaded('videos', fn() => $this->videos->map(fn($v) => [
                'id'          => $v->id,
                'title'       => $v->title,
                'url'         => $v->url,
                'description' => $v->description,
                'duration'    => $v->duration,
                'order'       => $v->order,
            ])),
            'questions'        => $this->whenLoaded('questions', fn() => $this->questions->map(fn($q) => [
                'id'            => $q->id,
                'question_text' => $q->question_text,
                'image'         => $q->image,
                'order'         => $q->order,
                'answers'       => $q->relationLoaded('answers')
                    ? $q->answers->map(fn($a) => [
                        'id'         => $a->id,
                        'text'       => $a->text,
                        // Only expose is_correct for owners/managers
                        'is_correct' => $request->user()?->isStudent() ? null : $a->is_correct,
                    ])
                    : [],
            ])),
            'student_progress' => $this->when(
                isset($this->resource->student_progress),
                fn() => $this->resource->student_progress
            ),
            'created_at'       => $this->created_at,
        ];
    }
}
