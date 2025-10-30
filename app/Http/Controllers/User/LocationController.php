<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LocationController extends Controller
{
    public function barangays(Request $request)
    {
        $municipalityKey = $request->query('municipality');
        if (! $municipalityKey) {
            return response()->json(['message' => 'Municipality is required.'], 422);
        }

        $municipalities = config('locations.municipalities', []);
        if (! isset($municipalities[$municipalityKey])) {
            return response()->json(['message' => 'Unknown municipality.'], 404);
        }

        $definition = $municipalities[$municipalityKey];
        $endpoint = rtrim($definition['endpoint'] ?? '', '/');
        $code = $definition['code'] ?? null;

        if (! $endpoint || ! $code) {
            return response()->json(['message' => 'Invalid municipality definition.'], 500);
        }

        $url = "https://psgc.gitlab.io/api/{$endpoint}/{$code}/barangays/";

        try {
            $response = Http::timeout(10)->acceptJson()->get($url);
            if (! $response->successful()) {
                Log::warning('Barangay lookup failed', [
                    'municipality' => $municipalityKey,
                    'status' => $response->status(),
                ]);

                return response()->json(['message' => 'Unable to fetch barangays at the moment.'], 502);
            }

            $barangays = collect($response->json() ?? [])
                ->pluck('name')
                ->filter()
                ->unique()
                ->sort()
                ->values()
                ->all();

            return response()->json([
                'label' => $definition['label'],
                'barangays' => $barangays,
            ]);
        } catch (\Throwable $e) {
            Log::error('Barangay lookup error', [
                'municipality' => $municipalityKey,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Unable to fetch barangays.'], 500);
        }
    }

    public function puroks(Request $request)
    {
        $municipality = $request->query('municipality');
        $barangay = $request->query('barangay');

        if (! $municipality || ! $barangay) {
            return response()->json(['message' => 'Municipality and barangay are required.'], 422);
        }

        $purokConfig = config('locations.puroks', []);
        $defaultPuroks = config('locations.default_puroks', []);

        $puroks = $defaultPuroks;
        if (isset($purokConfig[$municipality][$barangay]) && ! empty($purokConfig[$municipality][$barangay])) {
            $puroks = $purokConfig[$municipality][$barangay];
        }

        return response()->json([
            'puroks' => array_values(array_filter($puroks)),
            'uses_default' => ! isset($purokConfig[$municipality][$barangay]),
        ]);
    }
}
