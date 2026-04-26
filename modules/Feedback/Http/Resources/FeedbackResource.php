<?php

namespace Modules\Feedback\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FeedbackResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'rating'       => $this->rating,
            'comment'      => $this->comment,
            'created_at'   => $this->created_at?->toISOString(),
            'student'      => $this->whenLoaded('student', fn() => [
                'id'     => $this->student->id,
                'name'   => $this->student->name,
                'avatar' => $this->student->avatar ? url($this->student->avatar) : null,
            ]),
            'staff'        => $this->whenLoaded('staff', fn() => [
                'id'     => $this->staff->id,
                'name'   => $this->staff->name,
                'avatar' => $this->staff->avatar ? url($this->staff->avatar) : null,
            ]),
        ];
    }
}
