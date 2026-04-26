<?php

namespace Modules\Evaluations\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StudentEvaluationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                 => $this->id,
            'student_id'         => $this->student_id,
            'parking_score'      => $this->parking_score,
            'reverse_score'      => $this->reverse_score,
            'city_driving_score' => $this->city_driving_score,
            'steering_score'     => $this->steering_score,
            'rules_score'        => $this->rules_score,
            'confidence_score'   => $this->confidence_score,
            'comment'            => $this->comment,
            'final_status'       => $this->final_status,
            'average_score'      => $this->average_score,
            'evaluated_by'       => $this->evaluated_by,
            'evaluator_name'     => $this->evaluatedBy?->name,
            'updated_at'         => $this->updated_at?->format('Y-m-d H:i'),
        ];
    }
}
