<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MisOrLocations
{
    protected const BARANGAY_CACHE_PREFIX = 'locations.barangays.';

    public static function municipalities(): array
    {
        return collect(self::municipalityConfig())
            ->map(function ($definition, $key) {
                $label = $definition['label'] ?? Str::headline(str_replace('-', ' ', $key));

                return [
                    'id' => $key,
                    'name' => $label,
                    'label' => $label,
                    'code' => $definition['code'] ?? null,
                    'endpoint' => $definition['endpoint'] ?? null,
                ];
            })
            ->values()
            ->all();
    }

    public static function barangays(string $municipalityId): array
    {
        $definition = self::definition($municipalityId);

        if (! $definition) {
            return [];
        }

        $cacheKey = self::cacheKey($definition['id']);
        $cached = Cache::get($cacheKey);
        if (is_array($cached) && ! empty($cached)) {
            return $cached;
        }

        $barangays = self::fetchBarangays($definition['endpoint'], $definition['code']);

        if (! empty($barangays)) {
            Cache::put($cacheKey, $barangays, now()->addHours(6));
        }

        return $barangays;
    }

    public static function findMunicipality(?string $municipalityId): ?array
    {
        return self::definition($municipalityId);
    }

    public static function findBarangay(?string $municipalityId, ?string $barangayId): ?array
    {
        if (! $municipalityId || ! $barangayId) {
            return null;
        }

        $barangays = self::barangays($municipalityId);

        foreach ($barangays as $barangay) {
            if ((string) ($barangay['id'] ?? '') === (string) $barangayId) {
                return $barangay;
            }
        }

        foreach ($barangays as $barangay) {
            if (strcasecmp($barangay['name'] ?? '', (string) $barangayId) === 0) {
                return $barangay;
            }
        }

        return null;
    }

    protected static function municipalityConfig(): array
    {
        return config('locations.municipalities', []);
    }

    protected static function definition(?string $municipalityId): ?array
    {
        if (! $municipalityId) {
            return null;
        }

        $municipalities = self::municipalityConfig();
        if (! isset($municipalities[$municipalityId])) {
            return null;
        }

        $definition = $municipalities[$municipalityId];
        $label = $definition['label'] ?? Str::headline(str_replace('-', ' ', $municipalityId));

        return [
            'id' => $municipalityId,
            'name' => $label,
            'label' => $label,
            'code' => $definition['code'] ?? null,
            'endpoint' => $definition['endpoint'] ?? null,
        ];
    }

    protected static function fetchBarangays(?string $endpoint, ?string $code): array
    {
        $endpoint = trim((string) $endpoint, '/');
        $code = $code ? trim((string) $code) : null;

        if (! $endpoint || ! $code) {
            return [];
        }

        $url = "https://psgc.gitlab.io/api/{$endpoint}/{$code}/barangays/";

        try {
            $response = Http::timeout(10)->acceptJson()->get($url);

            if (! $response->successful()) {
                Log::warning('misor-locations: barangay fetch failed', [
                    'endpoint' => $endpoint,
                    'code' => $code,
                    'status' => $response->status(),
                ]);

                return [];
            }

            return collect($response->json() ?? [])
                ->map(function ($item) {
                    $barangayCode = $item['code'] ?? null;
                    $name = $item['name'] ?? null;

                    if (! $barangayCode || ! $name) {
                        return null;
                    }

                    return [
                        'id' => (string) $barangayCode,
                        'code' => (string) $barangayCode,
                        'name' => $name,
                    ];
                })
                ->filter()
                ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                ->values()
                ->all();
        } catch (\Throwable $exception) {
            Log::error('misor-locations: barangay fetch error', [
                'endpoint' => $endpoint,
                'code' => $code,
                'error' => $exception->getMessage(),
            ]);

            return [];
        }
    }

    protected static function cacheKey(string $municipalityId): string
    {
        return self::BARANGAY_CACHE_PREFIX . $municipalityId;
    }
}
