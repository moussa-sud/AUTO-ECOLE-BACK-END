<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Auth\Http\Resources\UserResource;

class RegisterController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'school_name' => 'required|string|max:255',
            'name'        => 'required|string|max:255',
            'email'       => 'required|email|unique:users,email',
            'password'    => 'required|string|min:8|confirmed',
            'phone'       => 'nullable|string|max:20',
            'city'        => 'nullable|string|max:100',
        ]);

        // Create the tenant (driving school)
        $tenant = Tenant::create([
            'school_name' => $request->school_name,
            'slug'        => Str::slug($request->school_name) . '-' . Str::random(6),
            'city'        => $request->city,
        ]);

        // Create the owner user
        $user = User::create([
            'tenant_id' => $tenant->id,
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => $request->password,
            'role'      => 'owner',
            'phone'     => $request->phone,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'School registered successfully.',
            'token'   => $token,
            'user'    => new UserResource($user->load('tenant')),
        ], 201);
    }
}
