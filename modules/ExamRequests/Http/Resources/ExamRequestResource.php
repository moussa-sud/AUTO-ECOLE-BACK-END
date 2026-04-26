<?php

namespace Modules\ExamRequests\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExamRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                      => $this->id,
            'status'                  => $this->status,
            'student'                 => $this->whenLoaded('student', fn() => [
                'id'    => $this->student->id,
                'name'  => $this->student->name,
                'email' => $this->student->email,
            ]),
            'manager_recommendation'  => $this->manager_recommendation,
            'manager_notes'           => $this->manager_notes,
            'manager_reviewed_at'     => $this->manager_reviewed_at,
            'manager'                 => $this->whenLoaded('manager', fn() => $this->manager ? [
                'id'   => $this->manager->id,
                'name' => $this->manager->name,
            ] : null),
            'owner_decision'          => $this->owner_decision,
            'owner_notes'             => $this->owner_notes,
            'owner_decided_at'        => $this->owner_decided_at,
            'owner'                   => $this->whenLoaded('owner', fn() => $this->owner ? [
                'id'   => $this->owner->id,
                'name' => $this->owner->name,
            ] : null),
            'exam_duration_days'      => $this->exam_duration_days,
            'exam_date'               => $this->exam_date?->toDateString(),
            'created_at'              => $this->created_at,
        ];
    }
}
