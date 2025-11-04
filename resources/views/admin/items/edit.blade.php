<!-- resources/views/admin/items/edit.blade.php -->
@php
    $categoryMap = collect($categories ?? [])->filter()->keyBy('id')->map(fn ($c) => $c['name'])->toArray();

    $displayCategoryName = $item->category;
    if (is_numeric($item->category) && isset($categoryMap[(int) $item->category])) {
        $displayCategoryName = $categoryMap[(int) $item->category];
    } else {
        $displayCategoryName = $displayCategoryName ?? '';
    }

    $normalizedCategoryCode = array_change_key_case($categoryCodeMap ?? [], CASE_LOWER);
    $primaryInstance = $item->instances->first();

    $categoryCodeForCategory = '';
    if (is_numeric($item->category)) {
        $padded = str_pad((int) $item->category, 4, '0', STR_PAD_LEFT);
        $categoryCodeForCategory = substr(preg_replace('/\D/', '', strtoupper($padded)), 0, 4);
    } else {
        $raw = $normalizedCategoryCode[strtolower((string) $item->category)] ?? ($primaryInstance->category_code ?? '');
        $categoryCodeForCategory = $raw ? substr(preg_replace('/\D/', '', strtoupper($raw)), 0, 4) : '';
    }

    // Get existing photo URL - photos are stored in items table, not instances
    $existingPath = $item->photo ?? '';
    $existingUrl = '';
    if ($existingPath) {
        // Check if photo is in storage (public disk)
        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($existingPath)) {
            $existingUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($existingPath);
        } 
        // Check if it's a full HTTP URL
        elseif (str_starts_with($existingPath, 'http')) {
            $existingUrl = $existingPath;
        } 
        // Check if it's in public directory (default photo or legacy path)
        elseif (file_exists(public_path($existingPath))) {
            $existingUrl = asset($existingPath);
        }
    }

    $hasModelNo = $item->instances->contains(fn ($inst) => filled($inst->model_no ?? null));
    $hasSerialNo = $item->instances->contains(fn ($inst) => filled($inst->serial_no ?? null));
@endphp

<form
    id="edit-item-form-{{ $item->id }}"
    method="POST"
    action="{{ route('items.update', $item->id) }}"
    enctype="multipart/form-data"
    class="space-y-6"
    data-property-form
    data-edit-item-form
    data-accordion-group="edit-item-{{ $item->id }}"
    data-modal-name="edit-item-{{ $item->id }}">
    @csrf
    @method('PUT')

    <div data-edit-feedback class="hidden rounded-md border border-transparent bg-green-50 px-4 py-3 text-sm text-green-700"></div>
    <div data-edit-error class="hidden rounded-md border border-transparent bg-red-50 px-4 py-3 text-sm text-red-700"></div>

    <input type="hidden" name="item_instance_id" value="{{ $primaryInstance->id ?? '' }}">

    <!-- Step 1: Item Information -->
    <div class="bg-gray-50 shadow-md hover:shadow-lg transition rounded-lg mt-6" data-accordion-item>
        <button
            type="button"
            class="w-full text-left focus:outline-none"
            data-accordion-trigger
            data-accordion-target="edit-item-{{ $item->id }}-info"
            aria-expanded="false">
            <div class="flex items-center justify-between p-4">
                <div class="flex items-center space-x-3">
                    <div class="bg-blue-100 text-blue-600 rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold">1</div>
                    <h4 class="text-lg font-semibold text-gray-900">Item Information</h4>
                </div>
                <svg class="w-5 h-5 text-gray-500 transition-transform duration-200" data-accordion-caret xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </div>
        </button>

        <div class="p-4 border-t border-gray-100 space-y-4" id="edit-item-{{ $item->id }}-info" data-accordion-panel>
            <div>
                <x-input-label for="name-{{ $item->id }}" value="Item Name" />
                <x-text-input
                    id="name-{{ $item->id }}"
                    name="name"
                    type="text"
                    class="mt-1 block w-full"
                    :value="old('name', $item->name)"
                    required
                    data-edit-field="name"
                />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="quantity-{{ $item->id }}" value="Quantity" />
                <x-text-input
                    id="quantity-{{ $item->id }}"
                    name="quantity_display"
                    type="number"
                    class="mt-1 block w-full bg-gray-100"
                    :value="old('quantity_display', $item->instances->count())"
                    disabled
                />
                <p class="mt-1 text-xs text-gray-500">Quantity reflects the total property number rows linked to this item.</p>
            </div>

            <div>
                <x-input-label for="description-{{ $item->id }}" value="Description" />
                <textarea
                    id="description-{{ $item->id }}"
                    name="description"
                    rows="3"
                    class="mt-1 block w-full border rounded px-3 py-2"
                    data-edit-field="description">{{ old('description', $primaryInstance?->notes) }}</textarea>
                <x-input-error :messages="$errors->get('description')" class="mt-2" />
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Photo Upload</label>
                <input
                    id="photo-{{ $item->id }}"
                    name="photo"
                    type="file"
                    accept="image/*"
                    data-filepond="true"
                    @if($existingUrl) data-initial-url="{{ $existingUrl }}" @endif
                    data-preview-height="120"
                    data-thumb-width="160" />
                <input type="hidden" name="existing_photo" value="{{ $existingPath }}">
            </div>

        </div>
    </div>

    <input type="hidden" name="category" value="{{ old('category', $item->category) }}" data-edit-field="category">
    <input type="hidden" name="category_code" value="{{ old('category_code', $categoryCodeForCategory) }}" data-edit-field="category-code">

    <!-- Step 2: Existing Property Numbers -->
    <div class="bg-gray-50 shadow-md hover:shadow-lg transition rounded-lg mt-6" data-accordion-item>
        <button
            type="button"
            class="w-full text-left focus:outline-none"
            data-accordion-trigger
            data-accordion-target="edit-item-{{ $item->id }}-instances"
            aria-expanded="false">
            <div class="flex items-center justify-between p-4">
                <div class="flex items-center space-x-3">
                    <div class="bg-green-100 text-green-600 rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold">2</div>
                    <h4 class="text-lg font-semibold text-gray-900">Existing Property Numbers</h4>
                </div>
                <svg class="w-5 h-5 text-gray-500 transition-transform duration-200" data-accordion-caret xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </div>
        </button>

        <div id="edit-item-{{ $item->id }}-instances" class="p-4 border-t border-gray-100 space-y-4" data-accordion-panel>
            <p class="text-sm text-gray-600">
                Fill out Year, Category Code, GLA, Serial, and Office for every row. Inputs with issues turn light red until corrected.
            </p>

            <div id="edit_instances_container" class="w-full space-y-3 max-h-72 overflow-auto p-3 border rounded-lg bg-white" aria-live="polite">
                @forelse ($item->instances as $inst)
                    <div class="flex items-center gap-4 edit-instance-row" data-instance-id="{{ $inst->id }}">
                        <div class="flex-none w-10 text-center">
                            <div class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-indigo-50 text-indigo-700 font-medium">{{ $loop->iteration }}</div>
                        </div>

                        <div class="flex-1 bg-indigo-50 rounded-lg px-3 py-2 overflow-x-auto">
                            <div class="flex items-center gap-2 flex-nowrap">
                                <input
                                    type="text"
                                    class="w-16 text-center text-sm rounded-md border px-2 py-1 bg-gray-100 instance-part-year"
                                    value="{{ $inst->year_procured ?? '' }}"
                                    placeholder="Year"
                                    inputmode="numeric"
                                    maxlength="4"
                                    readonly>
                                <div class="text-gray-500 select-none">-</div>

                                <input
                                    type="text"
                                    class="w-16 text-center text-sm rounded-md border px-2 py-1 bg-gray-100 instance-part-category"
                                    value="{{ $inst->category_code ?? $inst->category_id ?? '' }}"
                                    placeholder="Category"
                                    inputmode="numeric"
                                    maxlength="4"
                                    readonly>
                                <div class="text-gray-500 select-none">-</div>

                                <input
                                    type="text"
                                    class="w-16 text-center text-sm rounded-md border px-2 py-1 bg-gray-100 instance-part-gla"
                                    value="{{ $inst->gla ?? '' }}"
                                    placeholder="GLA"
                                    inputmode="numeric"
                                    maxlength="4"
                                    readonly>
                                <div class="text-gray-500 select-none">-</div>

                                <input
                                    type="text"
                                    class="w-16 text-center text-sm rounded-md border px-2 py-1 bg-white instance-part-serial"
                                    value="{{ $inst->serial ?? '' }}"
                                    placeholder="Serial"
                                    maxlength="5">
                                <div class="text-gray-500 select-none">-</div>

                                <input
                                    type="text"
                                    class="w-16 text-center text-sm rounded-md border px-2 py-1 bg-gray-100 instance-part-office"
                                    value="{{ $inst->office_code ?? '' }}"
                                    placeholder="Office"
                                    inputmode="numeric"
                                    maxlength="4"
                                    readonly>
                            </div>

                            <div class="flex justify-end">
                                <button
                                    type="button"
                                    class="instance-remove-btn inline-flex items-center justify-center text-red-600 p-1 rounded hover:bg-red-50"
                                    aria-label="Remove instance">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-600">No property numbers found for this item.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Step 3: Serial and Model No. -->
    <div class="bg-gray-50 shadow-md hover:shadow-lg transition rounded-lg mt-6" data-accordion-item data-edit-serial-section>
        <button
            type="button"
            class="w-full text-left focus:outline-none"
            data-accordion-trigger
            data-serial-model-trigger
            data-accordion-target="edit-item-{{ $item->id }}-serial-model"
            aria-expanded="false">
            <div class="flex items-center justify-between p-4">
                <div class="flex items-center space-x-3">
                    <div class="bg-blue-100 text-blue-600 rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold">3</div>
                    <h4 class="text-lg font-semibold text-gray-900">Serial and Model No.</h4>
                </div>
                <svg class="w-5 h-5 text-gray-500 transition-transform duration-200" data-accordion-caret xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </div>
        </button>

        <div id="edit-item-{{ $item->id }}-serial-model" class="p-4 border-t border-gray-100 space-y-4" data-accordion-panel data-edit-serial-panel>
            <p class="text-sm text-gray-600" data-edit-serial-message>
                Provide serial and model numbers per property number once property number rows are complete.
            </p>

            <div class="w-full space-y-3 max-h-72 overflow-auto p-3 border rounded-lg bg-white" data-edit-serial-container aria-live="polite">
                @forelse ($item->instances as $inst)
                    <div class="border border-gray-200 rounded-lg p-3 bg-indigo-50 space-y-3 edit-serial-row" data-instance-id="{{ $inst->id }}">
                        <div class="text-sm font-semibold text-gray-700">{{ $inst->property_number ?? 'N/A' }}</div>
                        <div class="flex flex-col md:flex-row gap-3">
                            <div class="flex-1">
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Serial No.</label>
                                <input
                                    type="text"
                                    maxlength="4"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm uppercase focus:border-purple-500 focus:ring focus:ring-purple-200 instance-part-serial-no"
                                    value="{{ $inst->serial_no ?? '' }}"
                                    data-serial-model-input="serial_no"
                                    data-instance-id="{{ $inst->id }}"
                                    @unless($hasSerialNo) disabled @endunless>
                            </div>
                            <div class="flex-1">
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Model No.</label>
                                <input
                                    type="text"
                                    maxlength="15"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm uppercase focus:border-purple-500 focus:ring focus:ring-purple-200 instance-part-model-no"
                                    value="{{ $inst->model_no ?? '' }}"
                                    data-serial-model-input="model_no"
                                    data-instance-id="{{ $inst->id }}"
                                    @unless($hasModelNo) disabled @endunless>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">No property numbers available.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="mt-4 border-t pt-4 flex justify-end gap-3 sticky bottom-0 bg-white z-20">
        <x-button
            variant="secondary"
            iconName="x-mark"
            type="button"
            data-edit-cancel
            x-on:click="$dispatch('close-modal', 'edit-item-{{ $item->id }}')">
            Cancel
        </x-button>

        <x-button
            variant="primary"
            iconName="arrow-path"
            type="submit"
            data-edit-submit>
            Update
        </x-button>
    </div>
</form>

