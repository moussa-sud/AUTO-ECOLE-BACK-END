<?php

namespace Modules\Settings\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json(['data' => $request->user()->tenant], 200);
    }

    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'school_name' => 'sometimes|string|max:255',
            'phone'       => 'nullable|string|max:20',
            'address'     => 'nullable|string|max:255',
            'city'        => 'nullable|string|max:100',
            'logo'        => 'nullable|string|url',
        ]);

        $tenant = $request->user()->tenant;
        $tenant->update($request->only('school_name', 'phone', 'address', 'city', 'logo'));

        return response()->json([
            'message' => 'School settings updated.',
            'data'    => $tenant->fresh(),
        ], 200);
    }
}
