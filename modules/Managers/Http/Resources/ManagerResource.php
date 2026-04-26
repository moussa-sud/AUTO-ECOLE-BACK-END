<?php

namespace Modules\Managers\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ManagerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'tenant_id'  => $this->tenant_id,
            'name'       => $this->name,
            'email'      => $this->email,
            'phone'      => $this->phone,
            'avatar'     => $this->avatar ? url($this->avatar) : null,
            'is_active'  => $this->is_active,
            'created_at' => $this->created_at,
        ];
    }
}
