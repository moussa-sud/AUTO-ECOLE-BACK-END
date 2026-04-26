<?php

namespace Modules\Reservations\Models;

use Illuminate\Database\Eloquent\Model;

class ReservationSetting extends Model
{
    protected $fillable = [
        'tenant_id',
        'working_days',
        'working_hours_start',
        'working_hours_end',
        'slot_duration_minutes',
        'max_students_per_slot',
        'cancellation_hours',
    ];

    protected function casts(): array
    {
        return [
            'working_days'          => 'array',
            'max_students_per_slot' => 'integer',
            'cancellation_hours'    => 'integer',
            'slot_duration_minutes' => 'integer',
        ];
    }
}
