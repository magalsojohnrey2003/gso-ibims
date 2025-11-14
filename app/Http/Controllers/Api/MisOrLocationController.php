<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\MisOrLocations;
use Illuminate\Http\JsonResponse;

class MisOrLocationController extends Controller
{
    public function municipalities(): JsonResponse
    {
        return response()->json([
            'data' => MisOrLocations::municipalities(),
        ]);
    }

    public function barangays(string $municipalityId): JsonResponse
    {
        return response()->json([
            'data' => MisOrLocations::barangays($municipalityId),
        ]);
    }
}
