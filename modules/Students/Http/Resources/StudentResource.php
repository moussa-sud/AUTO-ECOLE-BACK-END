<?php

namespace Modules\Students\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'tenant_id'        => $this->tenant_id,
            'name'             => $this->name,
            'email'            => $this->email,
            'phone'            => $this->phone,
            'avatar'           => $this->avatar ? url($this->avatar) : null,
            'is_active'        => $this->is_active,
            'results_count'    => $this->whenCounted('results'),
            'exam_requests_count' => $this->whenCounted('examRequests'),
            'created_at'       => $this->created_at,
        ];
    }
}
