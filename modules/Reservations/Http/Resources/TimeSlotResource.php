<?php

namespace Modules\Reservations\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TimeSlotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'start_time'   => substr($this->start_time, 0, 5),
            'end_time'     => substr($this->end_time, 0, 5),
            'is_active'    => $this->is_active,
            'booked_count' => $this->whenAppended('booked_count'),
            'is_full'      => $this->whenAppended('is_full'),
            'is_booked_by_me' => $this->whenAppended('is_booked_by_me'),
        ];
    }
}
