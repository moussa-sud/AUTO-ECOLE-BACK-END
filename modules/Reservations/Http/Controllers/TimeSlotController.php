<?php

namespace Modules\Reservations\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Reservations\Http\Resources\TimeSlotResource;
use Modules\Reservations\Models\Reservation;
use Modules\Reservations\Models\ReservationSetting;
use Modules\Reservations\Models\TimeSlot;

class TimeSlotController extends Controller
{
    /**
     * GET /api/time-slots?date=YYYY-MM-DD
     * Returns all active slots for the tenant, annotated with availability for the given date.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'required|date|after_or_equal:today',
        ]);

        $tenantId = $request->user()->tenant_id;
        $date     = Carbon::parse($request->date);
        $dayName  = strtolower($date->format('l')); // e.g. "monday"

        // Check if the date is a working day
        $settings = ReservationSetting::where('tenant_id', $tenantId)->first();
        if ($settings && !in_array($dayName, $settings->working_days ?? [])) {
            return response()->json(['data' => [], 'message' => 'This day is not a working day.'], 200);
        }

        $maxPerSlot = $settings?->max_students_per_slot ?? 1;

        // Load all active slots for this tenant
        $slots = TimeSlot::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('start_time')
            ->get();

        // Count active reservations per slot for this date
        $bookings = Reservation::where('tenant_id', $tenantId)
            ->where('date', $date->toDateString())
            ->whereIn('status', ['pending', 'confirmed'])
            ->get()
            ->groupBy('time_slot_id');

        $userId = $request->user()->id;

        $slots->each(function (TimeSlot $slot) use ($bookings, $maxPerSlot, $userId) {
            $slotBookings         = $bookings->get($slot->id, collect());
            $slot->booked_count   = $slotBookings->count();
            $slot->is_full        = $slot->booked_count >= $maxPerSlot;
            $slot->is_booked_by_me = $slotBookings->where('student_id', $userId)->isNotEmpty();
        });

        return response()->json(['data' => TimeSlotResource::collection($slots)], 200);
    }

    /**
     * POST /api/time-slots  (owner only)
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'start_time' => 'required|date_format:H:i',
            'end_time'   => 'required|date_format:H:i|after:start_time',
        ]);

        $slot = TimeSlot::create([
            'tenant_id'  => $request->user()->tenant_id,
            'start_time' => $request->start_time,
            'end_time'   => $request->end_time,
        ]);

        return response()->json(['message' => 'Time slot created.', 'data' => new TimeSlotResource($slot)], 201);
    }

    /**
     * PUT /api/time-slots/{slot}  (owner only)
     */
    public function update(Request $request, TimeSlot $slot): JsonResponse
    {
        $this->ensureSameTenant($request, $slot);

        $request->validate([
            'start_time' => 'sometimes|date_format:H:i',
            'end_time'   => 'sometimes|date_format:H:i|after:start_time',
            'is_active'  => 'sometimes|boolean',
        ]);

        $slot->update($request->only('start_time', 'end_time', 'is_active'));

        return response()->json(['message' => 'Time slot updated.', 'data' => new TimeSlotResource($slot->fresh())], 200);
    }

    /**
     * DELETE /api/time-slots/{slot}  (owner only)
     */
    public function destroy(Request $request, TimeSlot $slot): JsonResponse
    {
        $this->ensureSameTenant($request, $slot);
        $slot->delete();

        return response()->json(['message' => 'Time slot deleted.'], 200);
    }

    /**
     * POST /api/time-slots/generate  (owner only)
     * Auto-generate time slots based on settings.
     */
    public function generate(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $settings = ReservationSetting::where('tenant_id', $tenantId)->first();

        if (!$settings) {
            return response()->json(['message' => 'Please configure reservation settings first.'], 422);
        }

        // Delete existing slots for this tenant
        TimeSlot::where('tenant_id', $tenantId)->delete();

        $start    = Carbon::createFromFormat('H:i:s', $settings->working_hours_start);
        $end      = Carbon::createFromFormat('H:i:s', $settings->working_hours_end);
        $duration = $settings->slot_duration_minutes;
        $slots    = [];

        $current = $start->copy();
        while ($current->copy()->addMinutes($duration)->lte($end)) {
            $slot = TimeSlot::create([
                'tenant_id'  => $tenantId,
                'start_time' => $current->format('H:i'),
                'end_time'   => $current->copy()->addMinutes($duration)->format('H:i'),
            ]);
            $slots[] = $slot;
            $current->addMinutes($duration);
        }

        return response()->json([
            'message' => count($slots) . ' time slots generated.',
            'data'    => TimeSlotResource::collection(collect($slots)),
        ], 201);
    }

    /**
     * GET /api/time-slots/all  (owner/manager — view all slots without date filter)
     */
    public function all(Request $request): JsonResponse
    {
        $slots = TimeSlot::where('tenant_id', $request->user()->tenant_id)
            ->orderBy('start_time')
            ->get();

        return response()->json(['data' => TimeSlotResource::collection($slots)], 200);
    }

    private function ensureSameTenant(Request $request, TimeSlot $slot): void
    {
        if ($slot->tenant_id !== $request->user()->tenant_id) {
            abort(404);
        }
    }
}
