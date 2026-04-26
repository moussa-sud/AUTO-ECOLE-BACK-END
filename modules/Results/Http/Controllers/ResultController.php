<?php

namespace Modules\Results\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Results\Http\Resources\ResultResource;
use Modules\Results\Models\Result;

class ResultController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $results = Result::where('tenant_id', $request->user()->tenant_id)
            ->with(['user', 'series'])
            ->latest('completed_at')
            ->paginate(20);

        return response()->json([
            'data' => ResultResource::collection($results->items()),
            'meta' => [
                'current_page' => $results->currentPage(),
                'last_page'    => $results->lastPage(),
                'total'        => $results->total(),
            ],
        ], 200);
    }

    public function myResults(Request $request): JsonResponse
    {
        $results = Result::where('user_id', $request->user()->id)
            ->with('series')
            ->latest('completed_at')
            ->get();

        return response()->json(['data' => ResultResource::collection($results)], 200);
    }

    public function show(Request $request, Result $result): JsonResponse
    {
        if ($result->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized.');
        }

        return response()->json(['data' => new ResultResource($result->load('series'))], 200);
    }

    public function studentResults(Request $request, User $student): JsonResponse
    {
        if ($student->tenant_id !== $request->user()->tenant_id || $student->role !== 'student') {
            abort(404, 'Student not found.');
        }

        $results = Result::where('user_id', $student->id)
            ->where('tenant_id', $request->user()->tenant_id)
            ->with('series')
            ->latest('completed_at')
            ->get();

        return response()->json([
            'data' => [
                'student' => ['id' => $student->id, 'name' => $student->name, 'email' => $student->email],
                'results' => ResultResource::collection($results),
            ],
        ], 200);
    }
}
