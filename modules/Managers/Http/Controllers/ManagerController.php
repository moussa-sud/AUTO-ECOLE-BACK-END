<?php

namespace Modules\Managers\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\Managers\Http\Resources\ManagerResource;

class ManagerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $managers = User::where('tenant_id', $request->user()->tenant_id)
            ->where('role', 'manager')
            ->latest()
            ->paginate(15);

        return response()->json([
            'data' => ManagerResource::collection($managers->items()),
            'meta' => [
                'current_page' => $managers->currentPage(),
                'last_page'    => $managers->lastPage(),
                'per_page'     => $managers->perPage(),
                'total'        => $managers->total(),
            ],
        ], 200);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'phone'    => 'nullable|string|max:20',
        ]);

        $manager = User::create([
            'tenant_id' => $request->user()->tenant_id,
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => $request->password,
            'role'      => 'manager',
            'phone'     => $request->phone,
        ]);

        return response()->json([
            'message' => 'Manager created successfully.',
            'data'    => new ManagerResource($manager),
        ], 201);
    }

    public function show(Request $request, User $manager): JsonResponse
    {
        $this->ensureSameTenant($request, $manager);

        return response()->json(['data' => new ManagerResource($manager)], 200);
    }

    public function update(Request $request, User $manager): JsonResponse
    {
        $this->ensureSameTenant($request, $manager);

        $request->validate([
            'name'  => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
            'email' => 'sometimes|email|unique:users,email,' . $manager->id,
        ]);

        $manager->update($request->only('name', 'email', 'phone'));

        return response()->json([
            'message' => 'Manager updated successfully.',
            'data'    => new ManagerResource($manager->fresh()),
        ], 200);
    }

    public function destroy(Request $request, User $manager): JsonResponse
    {
        $this->ensureSameTenant($request, $manager);
        $manager->delete();

        return response()->json(['message' => 'Manager deleted successfully.'], 200);
    }

    public function toggleStatus(Request $request, User $manager): JsonResponse
    {
        $this->ensureSameTenant($request, $manager);
        $manager->update(['is_active' => !$manager->is_active]);

        return response()->json([
            'message' => 'Manager status updated.',
            'data'    => new ManagerResource($manager->fresh()),
        ], 200);
    }

    public function uploadAvatar(Request $request, User $manager): JsonResponse
    {
        $this->ensureSameTenant($request, $manager);

        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($manager->avatar) {
            Storage::disk('public')->delete(str_replace('/storage/', '', $manager->avatar));
        }

        $path = $request->file('avatar')->store('avatars', 'public');
        $manager->update(['avatar' => '/storage/' . $path]);

        return response()->json([
            'message' => 'تم تحديث الصورة بنجاح.',
            'data'    => new ManagerResource($manager->fresh()),
        ], 200);
    }

    public function removeAvatar(Request $request, User $manager): JsonResponse
    {
        $this->ensureSameTenant($request, $manager);

        if ($manager->avatar) {
            Storage::disk('public')->delete(str_replace('/storage/', '', $manager->avatar));
            $manager->update(['avatar' => null]);
        }

        return response()->json([
            'message' => 'تم حذف الصورة.',
            'data'    => new ManagerResource($manager->fresh()),
        ], 200);
    }

    private function ensureSameTenant(Request $request, User $manager): void
    {
        if ($manager->tenant_id !== $request->user()->tenant_id || $manager->role !== 'manager') {
            abort(404, 'Manager not found.');
        }
    }
}
