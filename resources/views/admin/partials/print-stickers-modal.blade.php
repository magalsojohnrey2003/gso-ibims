<x-modal name="print-stickers" maxWidth="lg">
    <div class="flex max-h-[85vh] flex-col overflow-hidden bg-white" data-print-modal>
        <div class="bg-purple-600 px-6 py-4 text-white">
            <h3 class="text-lg font-semibold tracking-wide">Print Stickers</h3>
            <p class="mt-1 text-xs text-purple-100" data-print-summary></p>
        </div>
        <form id="print-stickers-form" class="flex-1 space-y-5 overflow-y-auto px-6 py-5" data-print-form>
            <input type="hidden" data-print-route-input>
            <input type="hidden" data-print-quantity-input>
            <div class="space-y-2">
                <x-input-label for="print-person-accountable" value="Person Accountable" />
                <x-text-input
                    id="print-person-accountable"
                    type="text"
                    class="mt-1 block w-full"
                    autocomplete="off"
                    data-print-person>
                </x-text-input>
                <p class="text-xs text-gray-500">Optional: include who will receive the assets.</p>
            </div>
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-700">Signature</span>
                    <button type="button" class="text-xs text-blue-600 hover:underline" data-print-signature-clear>Clear</button>
                </div>
                <div class="overflow-hidden rounded-lg border border-gray-300 bg-white">
                    <canvas data-print-signature-canvas class="h-40 w-full touch-pan-y"></canvas>
                </div>
                <p class="text-xs text-gray-500">Sign using your mouse, trackpad, or finger. Leave blank if a handwritten signature will be applied later.</p>
            </div>
        </form>
        <div class="flex items-center justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
            <x-button
                type="button"
                variant="secondary"
                data-print-cancel
                iconName="x-mark">
                Cancel
            </x-button>
            <x-button
                type="submit"
                iconName="printer"
                data-print-submit
                form="print-stickers-form">
                Generate Stickers
            </x-button>
        </div>
    </div>
</x-modal>
