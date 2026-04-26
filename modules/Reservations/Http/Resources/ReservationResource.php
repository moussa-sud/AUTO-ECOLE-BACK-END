<?php

namespace Modules\Reservations\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReservationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'date'       => $this->date->format('Y-m-d'),
            'status'     => $this->status,
            'notes'      => $this->notes,
            'created_at' => $this->created_at->toISOString(),
            'time_slot'  => $this->whenLoaded('timeSlot', fn() => [
                'id'         => $this->timeSlot->id,
                'start_time' => substr($this->timeSlot->start_time, 0, 5),
                'end_time'   => substr($this->timeSlot->end_time, 0, 5),
            ]),
            'student'    => $this->whenLoaded('student', fn() => [
                'id'    => $this->student->id,
                'name'  => $this->student->name,
                'email' => $this->student->email,
                'phone' => $this->student->phone ?? null,
            ]),
        ];
    }
}
