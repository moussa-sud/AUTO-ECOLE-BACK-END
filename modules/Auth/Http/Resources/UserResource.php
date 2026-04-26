<?php

namespace Modules\Auth\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'tenant_id'  => $this->tenant_id,
            'name'       => $this->name,
            'email'      => $this->email,
            'role'       => $this->role,
            'phone'      => $this->phone,
            'avatar'     => $this->avatar,
            'is_active'  => $this->is_active,
            'tenant'     => $this->whenLoaded('tenant', fn() => [
                'id'          => $this->tenant->id,
                'school_name' => $this->tenant->school_name,
                'slug'        => $this->tenant->slug,
                'city'        => $this->tenant->city,
                'logo'        => $this->tenant->logo,
            ]),
            'created_at' => $this->created_at,
        ];
    }
}
