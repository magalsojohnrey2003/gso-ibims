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
@endphp

<form
    method="POST"
    action="{{ route('items.update', $item->id) }}"
    enctype="multipart/form-data"
    class="space-y-6"
    data-property-form
    data-edit-item-form
    data-modal-name="edit-item-{{ $item->id }}">
    @csrf
    @method('PUT')

    <div data-edit-feedback class="hidden rounded-md border border-transparent bg-green-50 px-4 py-3 text-sm text-green-700"></div>
    <div data-edit-error class="hidden rounded-md border border-transparent bg-red-50 px-4 py-3 text-sm text-red-700"></div>

    <input type="hidden" name="item_instance_id" value="{{ $primaryInstance->id ?? '' }}">

    <!-- Step 1: Basic Information -->
    <div class="bg-gray-50 shadow-md hover:shadow-lg transition rounded-lg p-4">
        <div class="flex items-center mb-4">
            <div class="bg-blue-100 text-blue-600 rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold mr-3">1</div>
            <h4 class="text-lg font-semibold text-gray-900">Item Information</h4>
        </div>

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

        <input type="hidden" name="category" value="{{ old('category', $item->category) }}" data-edit-field="category">
        <input type="hidden" name="category_code" value="{{ old('category_code', $categoryCodeForCategory) }}" data-edit-field="category-code">
    </div>

    <!-- Step 2: Existing Property Numbers -->
    <div class="bg-gray-50 shadow-md hover:shadow-lg transition rounded-lg p-4">
        <div class="flex items-center mb-4">
            <div class="bg-green-100 text-green-600 rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold mr-3">2</div>
            <h4 class="text-lg font-semibold text-gray-900">Existing Property Numbers</h4>
        </div>

        <p class="text-sm text-gray-600 mb-3">
            Fill out Year, Category Code, GLA, Serial, and Office for every row. Inputs with issues turn light red until corrected.
        </p>

        <div id="edit_instances_container" class="w-full space-y-3 max-h-72 overflow-auto p-3 border rounded-lg bg-white" aria-live="polite">
            @forelse ($item->instances as $inst)
                <div class="flex items-start gap-4 edit-instance-row" data-instance-id="{{ $inst->id }}">
                    <div class="flex-none w-10 text-center">
                        <div class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-indigo-50 text-indigo-700 font-medium">{{ $loop->iteration }}</div>
                    </div>

                    <div class="flex-1 bg-indigo-50 rounded-lg px-3 py-2 flex items-center gap-2 flex-wrap">
                        <input
                            type="text"
                            class="w-20 sm:w-16 text-center text-sm rounded-md border px-2 py-1 bg-white instance-part-year"
                            value="{{ $inst->year_procured ?? '' }}"
                            placeholder="Year"
                            inputmode="numeric"
                            maxlength="4">
                        <div class="text-gray-500 select-none"> - </div>

                        <input
                            type="text"
                            class="w-16 text-center text-sm rounded-md border px-2 py-1 bg-white instance-part-category"
                            value="{{ $inst->category_code ?? $inst->category_id ?? '' }}"
                            placeholder="Category"
                            inputmode="numeric"
                            maxlength="4">
                        <div class="text-gray-500 select-none"> - </div>

                        <input
                            type="text"
                            class="w-16 text-center text-sm rounded-md border px-2 py-1 bg-white instance-part-gla"
                            value="{{ $inst->gla ?? '' }}"
                            placeholder="GLA"
                            inputmode="numeric"
                            maxlength="4">
                        <div class="text-gray-500 select-none"> - </div>

                        <input
                            type="text"
                            class="w-20 sm:w-16 text-center text-sm rounded-md border px-2 py-1 bg-white instance-part-serial"
                            value="{{ $inst->serial ?? '' }}"
                            placeholder="Serial"
                            maxlength="5">
                        <div class="text-gray-500 select-none"> - </div>

                        <input
                            type="text"
                            class="w-20 sm:w-16 text-center text-sm rounded-md border px-2 py-1 bg-white instance-part-office"
                            value="{{ $inst->office_code ?? '' }}"
                            placeholder="Office"
                            inputmode="numeric"
                            maxlength="4">

                        <div class="flex-none ml-2">
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

    <!-- Step 3: Additional Details -->
    <div class="bg-gray-50 shadow-md hover:shadow-lg transition rounded-lg">
        <button
            type="button"
            data-step3-header
            aria-expanded="false"
            aria-controls="edit-step3-body-{{ $item->id }}"
            class="w-full text-left focus:outline-none">
            <div class="flex items-center justify-between p-4">
                <div class="flex items-center space-x-3">
                    <div class="bg-purple-100 text-purple-600 rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold">3</div>
                    <h4 class="text-lg font-semibold text-gray-900">Additional Details</h4>
                </div>
                <svg class="w-5 h-5 text-gray-500 transform transition-transform" data-step3-caret xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </div>
        </button>

        <div id="edit-step3-body-{{ $item->id }}" data-step3-body class="p-4 max-h-0 overflow-hidden opacity-0">
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

            @php
                $existingPath = $primaryInstance->photo ?? $item->photo ?? '';
                $existingUrl = '';
                if ($existingPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($existingPath)) {
                    $existingUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($existingPath);
                } elseif ($existingPath && str_starts_with($existingPath, 'http')) {
                    $existingUrl = $existingPath;
                }
            @endphp

            <div class="mt-4">
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

    <!-- Actions -->
    <div class="mt-4 border-t pt-4 flex justify-end gap-3">
        <x-button
            variant="secondary"
            iconName="x-mark"
            type="button"
            data-edit-cancel
            x-on:click="$dispatch('close-modal', 'edit-item-{{ $item->id }}')">
            Cancel
        </x-button>

        <x-button iconName="arrow-path" type="submit" data-edit-submit>Update</x-button>
    </div>
</form>
