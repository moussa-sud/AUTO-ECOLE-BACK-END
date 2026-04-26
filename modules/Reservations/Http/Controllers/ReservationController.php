<?php

namespace Modules\Reservations\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Reservations\Http\Resources\ReservationResource;
use Modules\Reservations\Models\Reservation;
use Modules\Reservations\Models\ReservationSetting;
use Modules\Reservations\Models\TimeSlot;

class ReservationController extends Controller
{
    /**
     * GET /api/reservations
     * - Student: returns own reservations
     * - Manager/Owner: returns all reservations, optionally filtered by date
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $user     = $request->user();

        $query = Reservation::where('reservations.tenant_id', $tenantId)
            ->with(['timeSlot', 'student'])
            ->orderBy('date', 'desc')
            ->orderBy('time_slot_id');

        if ($user->isStudent()) {
            $query->where('student_id', $user->id);
        }

        if ($request->filled('date')) {
            $request->validate(['date' => 'date']);
            $query->whereDate('date', $request->date);
        }

        if ($request->filled('date_from')) {
            $request->validate(['date_from' => 'date']);
            $query->whereDate('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $request->validate(['date_to' => 'date']);
            $query->whereDate('date', '<=', $request->date_to);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $reservations = $query->paginate($request->input('per_page', 20));

        return response()->json([
            'data' => ReservationResource::collection($reservations->items()),
            'meta' => [
                'current_page' => $reservations->currentPage(),
                'last_page'    => $reservations->lastPage(),
                'total'        => $reservations->total(),
            ],
        ], 200);
    }

    /**
     * POST /api/reservations  (student only)
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'date'         => 'required|date|after_or_equal:today',
            'time_slot_id' => 'required|integer|exists:time_slots,id',
            'notes'        => 'nullable|string|max:500',
        ]);

        $tenantId   = $request->user()->tenant_id;
        $studentId  = $request->user()->id;
        $date       = Carbon::parse($request->date);
        $dayName    = strtolower($date->format('l'));

        // Validate working day
        $settings = ReservationSetting::where('tenant_id', $tenantId)->first();
        if ($settings && !in_array($dayName, $settings->working_days ?? [])) {
            return response()->json(['message' => 'Reservations are not available on this day.'], 422);
        }

        // Validate time slot belongs to this tenant
        $slot = TimeSlot::where('id', $request->time_slot_id)
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->first();

        if (!$slot) {
            return response()->json(['message' => 'Invalid or inactive time slot.'], 422);
        }

        // Prevent student from booking more than 1 lesson per day
        $existingToday = Reservation::where('tenant_id', $tenantId)
            ->where('student_id', $studentId)
            ->where('date', $date->toDateString())
            ->whereIn('status', ['pending', 'confirmed'])
            ->exists();

        if ($existingToday) {
            return response()->json(['message' => 'You already have a reservation on this day.'], 422);
        }

        // Check slot capacity
        $maxPerSlot   = $settings?->max_students_per_slot ?? 1;
        $bookedCount  = Reservation::where('tenant_id', $tenantId)
            ->where('time_slot_id', $slot->id)
            ->where('date', $date->toDateString())
            ->whereIn('status', ['pending', 'confirmed'])
            ->count();

        if ($bookedCount >= $maxPerSlot) {
            return response()->json(['message' => 'This time slot is fully booked.'], 422);
        }

        // Create reservation
        try {
            $reservation = DB::transaction(function () use ($request, $tenantId, $studentId, $date, $slot) {
                return Reservation::create([
                    'tenant_id'    => $tenantId,
                    'student_id'   => $studentId,
                    'time_slot_id' => $slot->id,
                    'date'         => $date->toDateString(),
                    'status'       => 'pending',
                    'notes'        => $request->notes,
                ]);
            });
        } catch (\Illuminate\Database\QueryException $e) {
            if (str_contains($e->getMessage(), 'unique_student_slot_date')) {
                return response()->json(['message' => 'You already have a reservation for this slot.'], 422);
            }
            throw $e;
        }

        $reservation->load(['timeSlot', 'student']);

        return response()->json([
            'message' => 'Reservation created successfully.',
            'data'    => new ReservationResource($reservation),
        ], 201);
    }

    /**
     * GET /api/reservations/{reservation}
     */
    public function show(Request $request, Reservation $reservation): JsonResponse
    {
        $this->ensureAccess($request, $reservation);
        $reservation->load(['timeSlot', 'student']);

        return response()->json(['data' => new ReservationResource($reservation)], 200);
    }

    /**
     * PUT /api/reservations/{reservation}
     * - Student: can cancel (within cancellation window)
     * - Manager/Owner: can confirm or cancel
     */
    public function update(Request $request, Reservation $reservation): JsonResponse
    {
        $this->ensureAccess($request, $reservation);

        $user = $request->user();

        if ($user->isStudent()) {
            // Students can only cancel
            if ($reservation->student_id !== $user->id) {
                return response()->json(['message' => 'Forbidden.'], 403);
            }

            if ($reservation->status === 'cancelled') {
                return response()->json(['message' => 'Reservation is already cancelled.'], 422);
            }

            // Enforce cancellation window
            $settings = ReservationSetting::where('tenant_id', $user->tenant_id)->first();
            $hoursLimit = $settings?->cancellation_hours ?? 24;
            $lessonTime = Carbon::parse($reservation->date->format('Y-m-d') . ' ' . $reservation->timeSlot->start_time);

            if (now()->diffInHours($lessonTime, false) < $hoursLimit) {
                return response()->json([
                    'message' => "Cancellation is only allowed up to {$hoursLimit} hours before the lesson.",
                ], 422);
            }

            $reservation->update(['status' => 'cancelled']);
        } else {
            // Manager / Owner can set any status
            $request->validate([
                'status' => 'required|in:pending,confirmed,cancelled',
                'notes'  => 'nullable|string|max:500',
            ]);

            $reservation->update($request->only('status', 'notes'));
        }

        $reservation->load(['timeSlot', 'student']);

        return response()->json([
            'message' => 'Reservation updated.',
            'data'    => new ReservationResource($reservation),
        ], 200);
    }

    /**
     * DELETE /api/reservations/{reservation}  (owner only)
     */
    public function destroy(Request $request, Reservation $reservation): JsonResponse
    {
        $this->ensureAccess($request, $reservation);
        $reservation->delete();

        return response()->json(['message' => 'Reservation deleted.'], 200);
    }

    private function ensureAccess(Request $request, Reservation $reservation): void
    {
        if ($reservation->tenant_id !== $request->user()->tenant_id) {
            abort(404);
        }
    }
}
