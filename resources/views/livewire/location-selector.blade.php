<div class="space-y-4" wire:key="location-selector">
    <div>
        <x-input-label for="location_municipality" value="Municipality" />
        <select id="location_municipality"
                wire:model.live="selectedMunicipality"
                class="mt-1 w-full rounded-md border border-gray-600 bg-white px-3 py-2 text-gray-800">
            <option value="">Select municipality</option>
            @foreach($municipalities as $key => $definition)
                <option value="{{ $key }}">
                    {{ $definition['label'] ?? $key }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <x-input-label for="location_barangay" value="Barangay" />
        <select id="location_barangay"
                wire:model.live="selectedBarangay"
                class="mt-1 w-full rounded-md border border-gray-600 bg-white px-3 py-2 text-gray-800"
                @disabled(empty($barangays))
        >
            <option value="">Select barangay</option>
            @foreach($barangays as $barangay)
                <option value="{{ $barangay }}">{{ $barangay }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <x-input-label for="location_purok" value="Purok / Zone / Sitio" />
        <input
            id="location_purok"
            type="text"
            wire:model.live="selectedPurok"
            class="mt-1 w-full rounded-md border border-gray-600 bg-white px-3 py-2 text-gray-800"
            placeholder="e.g. Purok 3, Zone 2 or Sitio Mabini"
            list="location_purok_options"
        />
        @if(!empty($puroks))
            <datalist id="location_purok_options">
                @foreach($puroks as $purok)
                    <option value="{{ $purok }}"></option>
                @endforeach
            </datalist>
        @endif
    </div>


    <div>
        <x-input-label for="location_display" value="Address Preview" />
        <div id="location_display" class="mt-1 w-full border border-purple-500 bg-gray-50 text-gray-800 rounded-md px-3 py-2" style="border-width:2px; min-height:2.5rem;">
            {{ $locationValue ?: 'No address selected yet.' }}
        </div>
    </div>

    <input type="hidden" id="location" name="location" value="{{ $locationStoredValue }}">
    <x-input-error :messages="$errors->get('location')" class="mt-1" />
</div>
