<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class LocationSelector extends Component
{
    public array $municipalities = [];
    public array $barangays = [];
    public array $puroks = [];

    public ?string $selectedMunicipality = null;
    public ?string $selectedBarangay = null;
    public ?string $selectedPurok = null;

    public string $locationValue = '';

    public function mount(?string $initialMunicipalityKey = null, ?string $initialBarangay = null, ?string $initialPurok = null): void
    {
        $this->municipalities = config('locations.municipalities', []);

        if ($initialMunicipalityKey && isset($this->municipalities[$initialMunicipalityKey])) {
            $this->selectedMunicipality = $initialMunicipalityKey;
            $this->loadBarangays($initialMunicipalityKey, preset: $initialBarangay);
        }

        if ($initialBarangay) {
            $this->selectedBarangay = $initialBarangay;
            $this->loadPuroks(preset: $initialPurok);
        }

        if ($initialPurok) {
            $this->selectedPurok = $initialPurok;
        }

        $this->updateLocationValue();
    }

    public function updatedSelectedMunicipality($value): void
    {
        $this->selectedBarangay = null;
        $this->selectedPurok = null;
        $this->barangays = [];
        $this->puroks = [];

        $this->loadBarangays($value);
        $this->updateLocationValue();
    }

    public function updatedSelectedBarangay($value): void
    {
        $this->selectedPurok = null;
        $this->loadPuroks();
        $this->updateLocationValue();
    }

    public function updatedSelectedPurok($value): void
    {
        $this->updateLocationValue();
    }

    protected function loadBarangays(?string $municipalityKey, ?string $preset = null): void
    {
        if (! $municipalityKey || ! isset($this->municipalities[$municipalityKey])) {
            $this->barangays = [];
            return;
        }

        $definition = $this->municipalities[$municipalityKey];
        $endpoint = rtrim($definition['endpoint'] ?? '', '/');
        $code = $definition['code'] ?? null;

        if (! $endpoint || ! $code) {
            $this->barangays = [];
            return;
        }

        try {
            $url = "https://psgc.gitlab.io/api/{$endpoint}/{$code}/barangays/";
            $response = Http::timeout(10)->acceptJson()->get($url);

            if ($response->successful()) {
                $this->barangays = collect($response->json() ?? [])
                    ->pluck('name')
                    ->filter()
                    ->unique()
                    ->sort()
                    ->values()
                    ->all();
            } else {
                Log::warning('location-selector: barangay lookup failed', [
                    'municipality' => $municipalityKey,
                    'status' => $response->status(),
                ]);
                $this->barangays = [];
            }
        } catch (\Throwable $exception) {
            Log::error('location-selector: barangay lookup error', [
                'municipality' => $municipalityKey,
                'error' => $exception->getMessage(),
            ]);
            $this->barangays = [];
        }

        if ($preset && in_array($preset, $this->barangays, true)) {
            $this->selectedBarangay = $preset;
        }
    }

    protected function loadPuroks(?string $municipalityKey = null, ?string $barangay = null, ?string $preset = null): void
    {
        $municipalityLabel = $this->getMunicipalityLabel($municipalityKey ?? $this->selectedMunicipality);
        $barangayValue = $barangay ?? $this->selectedBarangay;

        if (! $municipalityLabel || ! $barangayValue) {
            $this->puroks = [];
            return;
        }

        $purokConfig = config('locations.puroks', []);
        $defaultPuroks = config('locations.default_puroks', []);

        $this->puroks = array_values(array_filter(
            $purokConfig[$municipalityLabel][$barangayValue] ?? $defaultPuroks
        ));

        if ($preset && in_array($preset, $this->puroks, true)) {
            $this->selectedPurok = $preset;
        }
    }

    protected function updateLocationValue(): void
    {
        $municipalityLabel = $this->getMunicipalityLabel($this->selectedMunicipality);

        if ($municipalityLabel && $this->selectedBarangay && $this->selectedPurok) {
            $this->locationValue = "{$municipalityLabel}, {$this->selectedBarangay}, {$this->selectedPurok}";
            $this->dispatch('location-updated', valid: true, value: $this->locationValue);
        } else {
            $this->locationValue = '';
            $this->dispatch('location-updated', valid: false, value: null);
        }
    }

    protected function getMunicipalityLabel(?string $key): ?string
    {
        if (! $key || ! isset($this->municipalities[$key])) {
            return null;
        }

        return $this->municipalities[$key]['label'] ?? $key;
    }

    public function render()
    {
        return view('livewire.location-selector');
    }
}
