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

    $existingPath = $item->photo ?? '';
    $hasCustomPhoto = filled($existingPath);
    $currentPhotoUrl = $item->photo_url;
    $defaultPhotoUrl = asset('images/item.png');

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
    data-modal-name="edit-item-{{ $item->id }}"
    data-photo-url="{{ $currentPhotoUrl }}"
    data-photo-default="{{ $defaultPhotoUrl }}"
    data-photo-custom="{{ $hasCustomPhoto ? 'true' : 'false' }}">
    @csrf
    @method('PUT')

    <div data-edit-feedback class="hidden rounded-md border border-transparent bg-green-50 px-4 py-3 text-sm text-green-700"></div>
    <div data-edit-error class="hidden rounded-md border border-transparent bg-red-50 px-4 py-3 text-sm text-red-700"></div>

    <input type="hidden" name="item_instance_id" value="{{ $primaryInstance->id ?? '' }}">

    <!-- Step 1: Item Information -->
    <div class="bg-gray-50 dark:bg-gray-800 shadow-md hover:shadow-lg transition rounded-lg mt-6" data-accordion-item>
        <button
            type="button"
            class="w-full text-left focus:outline-none"
            data-accordion-trigger
            data-accordion-target="edit-item-{{ $item->id }}-info"
            aria-expanded="false">
            <div class="flex items-center justify-between p-4">
                <div class="flex items-center space-x-3">
                    <div class="bg-blue-100 text-blue-600 rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold">1</div>
                    <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Item Information</h4>
                </div>
                <svg class="w-5 h-5 text-gray-500 transition-transform duration-200" data-accordion-caret xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v12m6-6H6" />
                </svg>
            </div>
        </button>

        <div class="p-4 border-t border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800 space-y-4" id="edit-item-{{ $item->id }}-info" data-accordion-panel>
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
                    class="mt-1 block w-full"
                    :value="old('quantity_display', $item->instances->count())"
                    min="{{ $item->instances->count() }}"
                    data-quantity-input
                    data-initial-quantity="{{ $item->instances->count() }}"
                />
                <p class="mt-1 text-xs text-gray-500">You can increase quantity to add new property number rows. Decreasing is not allowed.</p>
            </div>

            <div>
                <x-input-label for="description-{{ $item->id }}" value="Description" />
                <textarea
                    id="description-{{ $item->id }}"
                    name="description"
                    rows="3"
                    class="gov-input block w-full px-3 py-2 text-sm transition duration-200 mt-1"
                    data-edit-field="description">{{ old('description', $primaryInstance?->notes) }}</textarea>
                <x-input-error :messages="$errors->get('description')" class="mt-2" />
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Photo Upload</label>
                <div class="mb-3 flex items-center gap-4">
                    <img
                        id="edit-item-photo-preview-{{ $item->id }}"
                        src="{{ $currentPhotoUrl }}"
                        alt="Current photo for {{ $item->name }}"
                        class="h-24 w-24 rounded-lg border border-gray-200 object-cover shadow-sm"
                        data-edit-photo-preview
                        data-default-photo="{{ $defaultPhotoUrl }}">
                    <p class="text-xs text-gray-500 leading-4">
                        This thumbnail is used in the Items table and overview cards.
                    </p>
                </div>
                <input
                    id="photo-{{ $item->id }}"
                    name="photo"
                    type="file"
                    accept="image/*"
                    data-filepond="true"
                    data-preview-height="120"
                    data-thumb-width="160" />
                <input type="hidden" name="existing_photo" value="{{ $existingPath }}">
            </div>

        </div>
    </div>

    <input type="hidden" name="category" value="{{ old('category', $item->category) }}" data-edit-field="category">
    <input type="hidden" name="category_code" value="{{ old('category_code', $categoryCodeForCategory) }}" data-edit-field="category-code">

    <!-- Step 2: Existing Property Numbers -->
    <div class="bg-gray-50 dark:bg-gray-800 shadow-md hover:shadow-lg transition rounded-lg mt-6" data-accordion-item>
        <button
            type="button"
            class="w-full text-left focus:outline-none"
            data-accordion-trigger
            data-accordion-target="edit-item-{{ $item->id }}-instances"
            aria-expanded="false">
            <div class="flex items-center justify-between p-4">
                <div class="flex items-center space-x-3">
                    <div class="bg-green-100 text-green-600 rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold">2</div>
                    <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Existing Property Numbers</h4>
                </div>
                <svg class="w-5 h-5 text-gray-500 transition-transform duration-200" data-accordion-caret xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v12m6-6H6" />
                </svg>
            </div>
        </button>

        <div id="edit-item-{{ $item->id }}-instances" class="p-4 border-t border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800 space-y-4" data-accordion-panel>
            <p class="text-sm text-gray-600">
                Fill out Year, Category Code, GLA, Serial, and Office for every row. Inputs with issues turn light red until corrected.
            </p>

            <div id="edit_instances_container" class="w-full space-y-3 max-h-72 overflow-auto p-3 border rounded-lg bg-white dark:bg-gray-800" aria-live="polite" data-edit-instances-container>
                @forelse ($item->instances as $inst)
                    <div class="flex items-center gap-2 edit-instance-row bg-indigo-50 dark:bg-indigo-900/30 rounded-lg px-3 py-3" data-instance-id="{{ $inst->id }}">
                        <div class="flex-none w-8 text-center">
                            <div class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-indigo-100 dark:bg-indigo-800 text-indigo-700 dark:text-indigo-200 font-medium text-sm">{{ $loop->iteration }}</div>
                        </div>

                        <div class="flex items-center gap-2 flex-1">
                            <input
                                type="text"
                                class="gov-input rounded-md w-16 text-center text-sm px-2 py-1 bg-gray-100 dark:bg-gray-700/80 transition duration-200 instance-part-year"
                                value="{{ $inst->year_procured ?? '' }}"
                                placeholder="Year"
                                inputmode="numeric"
                                maxlength="4"
                                readonly>
                            <span class="text-gray-500 dark:text-gray-400 select-none">-</span>

                            <input
                                type="text"
                                class="gov-input rounded-md w-16 text-center text-sm px-2 py-1 bg-gray-100 dark:bg-gray-700/80 transition duration-200 instance-part-category"
                                value="{{ $inst->category_code ?? $inst->category_id ?? '' }}"
                                placeholder="Category"
                                inputmode="numeric"
                                maxlength="4"
                                readonly>
                            <span class="text-gray-500 dark:text-gray-400 select-none">-</span>

                            <input
                                type="text"
                                class="gov-input rounded-md w-16 text-center text-sm px-2 py-1 bg-gray-100 dark:bg-gray-700/80 transition duration-200 instance-part-gla"
                                value="{{ $inst->gla ?? '' }}"
                                placeholder="GLA"
                                inputmode="numeric"
                                maxlength="4"
                                readonly>
                            <span class="text-gray-500 dark:text-gray-400 select-none">-</span>

                            <input
                                type="text"
                                class="gov-input rounded-md w-20 text-center text-sm px-2 py-1 transition duration-200 instance-part-serial"
                                value="{{ $inst->serial ?? '' }}"
                                placeholder="Serial"
                                maxlength="5">
                            <span class="text-gray-500 dark:text-gray-400 select-none">-</span>

                            <select
                                class="gov-input rounded-md w-24 text-center text-sm px-2 py-1 transition duration-200 instance-part-office"
                                data-office-select
                                data-sync-office>
                                <option value="">Office</option>
                                <option value="{{ $inst->office_code ?? '' }}" selected>{{ $inst->office_code ?? '' }}</option>
                            </select>
                        </div>

                        <button
                            type="button"
                            class="instance-remove-btn flex-none inline-flex items-center justify-center text-red-600 hover:text-red-700 p-2 rounded hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                            aria-label="Remove instance">
                            <i class="fas fa-trash text-sm"></i>
                        </button>
                    </div>
                @empty
                    <p class="text-sm text-gray-600 dark:text-gray-400">No property numbers found for this item.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Step 3: Serial and Model No. -->
    <div class="bg-gray-50 dark:bg-gray-800 shadow-md hover:shadow-lg transition rounded-lg mt-6" data-accordion-item data-edit-serial-section>
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
                    <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Serial and Model No.</h4>
                </div>
                <svg class="w-5 h-5 text-gray-500 transition-transform duration-200" data-accordion-caret xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v12m6-6H6" />
                </svg>
            </div>
        </button>

        <div id="edit-item-{{ $item->id }}-serial-model" class="p-4 border-t border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800 space-y-4" data-accordion-panel data-edit-serial-panel>
            <p class="text-sm text-gray-600" data-edit-serial-message>
                Provide serial and model numbers per property number once property number rows are complete.
            </p>

            <!-- Generate Model No. (for all rows) -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Generate Model No. (for all rows)</label>
                    <input
                        type="text"
                        maxlength="100"
                        class="gov-input w-full px-3 py-2 text-sm uppercase transition duration-200"
                        placeholder="Type a model number to apply to all rows"
                        data-model-generator>
                </div>
                <div class="text-xs text-gray-500 md:text-right">Applies to every Model No. field below.</div>
            </div>

            <div class="w-full space-y-3 max-h-72 overflow-auto p-3 border rounded-lg bg-white dark:bg-gray-800" data-edit-serial-container aria-live="polite">
                @forelse ($item->instances as $inst)
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-3 bg-indigo-50 dark:bg-indigo-900/30 space-y-3 edit-serial-row" data-instance-id="{{ $inst->id }}">
                        <div class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ $inst->property_number ?? 'N/A' }}</div>
                        <div class="flex flex-col md:flex-row gap-3">
                            <div class="flex-1">
                                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Serial No.</label>
                                <input
                                    type="text"
                                    maxlength="100"
                                    class="gov-input w-full px-3 py-2 text-sm uppercase transition duration-200 instance-part-serial-no"
                                    value="{{ $inst->serial_no ?? '' }}"
                                    data-serial-model-input="serial_no"
                                    data-instance-id="{{ $inst->id }}"
                                    @unless($hasSerialNo) disabled @endunless>
                            </div>
                            <div class="flex-1">
                                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Model No.</label>
                                <input
                                    type="text"
                                    maxlength="100"
                                    class="gov-input w-full px-3 py-2 text-sm uppercase transition duration-200 instance-part-model-no"
                                    value="{{ $inst->model_no ?? '' }}"
                                    data-serial-model-input="model_no"
                                    data-instance-id="{{ $inst->id }}"
                                    @unless($hasModelNo) disabled @endunless>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">No property numbers available.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="mt-4 pt-2 flex justify-end gap-3 sticky bottom-0 z-20 pb-2">
        <!-- Edit Button (shown in readonly state) -->
        <x-button
            variant="primary"
            iconName="pencil"
            type="button"
            data-edit-mode-btn
            x-on:click="window.dispatchEvent(new CustomEvent('edit-item:enable-edit', { detail: { itemId: '{{ $item->id }}' } }))">
            Edit
        </x-button>

        <!-- Cancel Button (hidden in readonly state) -->
        <x-button
            variant="secondary"
            iconName="x-mark"
            type="button"
            data-edit-cancel-btn
            class="hidden"
            x-on:click="window.dispatchEvent(new CustomEvent('edit-item:cancel-edit', { detail: { itemId: '{{ $item->id }}' } }))">
            Cancel
        </x-button>

        <!-- Update Button (hidden in readonly state) -->
        <x-button
            variant="primary"
            iconName="arrow-path"
            type="submit"
            data-edit-submit
            class="hidden">
            Update
        </x-button>
    </div>
</form>
