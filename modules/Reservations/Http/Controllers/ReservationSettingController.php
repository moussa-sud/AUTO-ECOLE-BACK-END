<?php

namespace Modules\Reservations\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Reservations\Models\ReservationSetting;

class ReservationSettingController extends Controller
{
    /**
     * GET /api/reservation-settings
     */
    public function show(Request $request): JsonResponse
    {
        $settings = ReservationSetting::firstOrCreate(
            ['tenant_id' => $request->user()->tenant_id],
            [
                'working_days'          => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'],
                'working_hours_start'   => '08:00:00',
                'working_hours_end'     => '18:00:00',
                'slot_duration_minutes' => 60,
                'max_students_per_slot' => 1,
                'cancellation_hours'    => 24,
            ]
        );

        return response()->json(['data' => $settings], 200);
    }

    /**
     * PUT /api/reservation-settings  (owner only)
     */
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'working_days'          => 'sometimes|array',
            'working_days.*'        => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'working_hours_start'   => 'sometimes|date_format:H:i',
            'working_hours_end'     => 'sometimes|date_format:H:i|after:working_hours_start',
            'slot_duration_minutes' => 'sometimes|integer|in:30,45,60,90,120',
            'max_students_per_slot' => 'sometimes|integer|min:1|max:20',
            'cancellation_hours'    => 'sometimes|integer|min:0|max:168',
        ]);

        $settings = ReservationSetting::updateOrCreate(
            ['tenant_id' => $request->user()->tenant_id],
            $request->only(
                'working_days',
                'working_hours_start',
                'working_hours_end',
                'slot_duration_minutes',
                'max_students_per_slot',
                'cancellation_hours'
            )
        );

        return response()->json(['message' => 'Settings updated.', 'data' => $settings], 200);
    }
}
