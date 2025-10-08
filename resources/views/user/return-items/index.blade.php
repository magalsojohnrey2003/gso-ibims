<x-app-layout>
    <div class="p-6 max-w-7xl mx-auto space-y-6" id="returnItemsApp">
        <div>
            <x-title level="h2" size="2xl" weight="bold" icon="arrow-path" variant="s" iconStyle="plain" iconColor="gov-accent">
                Return Items
            </x-title>
            <p class="mt-2 text-sm text-gray-600">Manage and return borrowed items here.</p>
        </div>

        <div id="toastContainer" class="fixed top-4 right-4 z-[9999]"></div>
        <template id="alert-success-template">
            <x-alert type="success"><span data-alert-message></span></x-alert>
        </template>
        <template id="alert-error-template">
            <x-alert type="error"><span data-alert-message></span></x-alert>
        </template>
        <template id="alert-info-template">
            <x-alert type="info"><span data-alert-message></span></x-alert>
        </template>

        <div class="rounded-3xl bg-white shadow-sm border p-5 space-y-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:gap-6">
                <div class="relative flex-1 min-w-[240px]">
                    <input id="returnSearch" type="search" placeholder="Search by property number, serial, or request #"
                        class="w-full rounded-xl border border-gray-200 px-4 py-2.5 pr-24 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500"
                        autocomplete="off" />
                    <button id="clearSearch" type="button"
                    class="absolute inset-y-0 right-0 m-1 px-4 py-2 text-sm rounded-lg bg-gray-200 hover:bg-gray-300 text-gray-700 focus:outline-none focus:ring-2 focus:ring-purple-500">
                        Clear
                    </button>

                    <div id="returnSearchSuggestions"
                        class="absolute left-0 right-0 mt-1 hidden rounded-xl border border-gray-200 bg-white shadow-lg z-20 overflow-hidden"></div>
                </div>

                <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                    <select id="filterDue"
                        class="rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <option value="all">All requests</option>
                        <option value="due_7">Due within 3 days</option>
                        <option value="overdue">Overdue</option>
                    </select>
                    <x-button id="selectVisibleBtn" variant="secondary" class="px-6 py-2 text-sm bg-purple-600 text-black rounded-xl shadow-md hover:bg-purple-300 focus:outline-none focus:ring-2 focus:ring-purple-500">
                        Select All
                    </x-button>
                </div>

                <div class="text-sm text-gray-600 lg:ml-auto">
                    <span id="totalCount">0</span> request(s)
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)] items-start">
                <div class="space-y-4">
                    <div id="returnItemsGrid" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        <div class="rounded-xl border border-dashed border-gray-200 p-6 text-sm text-gray-500 bg-gray-50">
                            Loading borrowed items...
                        </div>
                    </div>
                    <button id="loadMoreBtn" type="button"
                        class="hidden w-full rounded-xl border border-gray-200 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">
                        Load more
                    </button>
                    <div id="returnListSentinel" class="h-1"></div>
                </div>

                <aside id="previewPanel"
                    class="hidden lg:block rounded-2xl border border-gray-200 bg-gradient-to-b from-white to-gray-50 shadow-sm p-5 sticky top-24">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-800 flex items-center gap-2">
                            <i class="fa-solid fa-eye text-purple-600"></i>
                            Preview
                        </h3>
                        <span id="previewBadge" class="text-xs uppercase tracking-wide text-gray-500">No selection</span>
                    </div>
                    <div id="previewContent" class="mt-4 hidden space-y-4 text-sm text-gray-700">
                        <div>
                            <div class="text-xs text-gray-500">Request</div>
                            <div id="previewRequestId" class="font-semibold text-gray-900"></div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500">Schedule</div>
                            <div id="previewDates"></div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500">Borrower</div>
                            <div id="previewBorrower"></div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500">Items</div>
                            <ul id="previewItems" class="mt-1 space-y-2 text-sm"></ul>
                        </div>
                    </div>
                    <div id="previewEmpty" class="mt-6 text-sm text-gray-500">
                        Select a borrow request to see its details.
                    </div>
                </aside>
            </div>
        </div>
    </div>

    <button id="fabReturn" type="button"
        class="fixed bottom-6 right-6 hidden items-center gap-3 rounded-full bg-purple-600 px-5 py-3 text-sm font-semibold text-white shadow-lg hover:bg-purple-700 focus:outline-none focus:ring-4 focus:ring-purple-300">
        <span id="fabLabel">Return items</span>
        <span id="fabCount"
            class="inline-flex h-6 min-w-[1.5rem] items-center justify-center rounded-full bg-white px-2 text-xs font-bold text-purple-600">
            0
        </span>
    </button>

    <x-modal name="return-selected-items" maxWidth="2xl">
        <div class="p-6 space-y-6" data-return-modal>
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Return Selected Items</h2>
                    <p class="text-sm text-gray-500" id="returnModalSubtitle">Review and confirm the items you want to return.</p>
                </div>
                <button type="button" class="text-gray-400 hover:text-gray-600" data-return-close>
                    <i class="fa-solid fa-times-circle text-lg"></i>
                </button>
            </div>

            <div class="space-y-4">
                <div class="flex items-center gap-3">
                    <div class="h-2 flex-1 overflow-hidden rounded-full bg-gray-200">
                        <div id="returnModalProgress" class="h-full w-1/4 rounded-full bg-purple-600 transition-all"></div>
                    </div>
                    <span id="returnModalStepLabel" class="text-xs uppercase tracking-wide text-gray-500">Step 1 of 4</span>
                </div>

                <div data-return-step="items" class="space-y-4"></div>
                <div data-return-step="conditions" class="space-y-4 hidden"></div>
                <div data-return-step="serials" class="space-y-4 hidden"></div>
                <div data-return-step="summary" class="space-y-4 hidden"></div>
            </div>

            <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                <x-button variant="secondary" class="hidden" data-return-prev>Previous</x-button>
                <div class="flex items-center gap-3 ml-auto">
                    <x-button variant="primary" data-return-next>Next</x-button>
                    <x-button variant="primary" class="hidden" data-return-submit>Confirm Return</x-button>
                </div>
            </div>
        </div>
    </x-modal>

    <script>
        window.RETURN_LIST_ROUTE = "{{ route('user.return.items.list') }}";
        window.RETURN_SUBMIT_ROUTE = "{{ route('user.return.items.request') }}";
        window.CSRF_TOKEN = "{{ csrf_token() }}";
        window.BORROW_SHOW_BASE = "{{ url('/user/my-borrowed-items') }}";

          document.addEventListener('DOMContentLoaded', function () {
            const inputElement = document.getElementById('returnModalItemSearch');
            
            inputElement.addEventListener('input', function(event) {
                // Normal left-to-right typing behavior
                const inputText = inputElement.value;
                console.log(inputText); // Log the input value (you can remove this line)
            });

            // Optional: Reverse text when the input field loses focus (if needed)
            inputElement.addEventListener('blur', function () {
                const reversedValue = inputElement.value.split('').reverse().join('');
                inputElement.value = reversedValue;
            });

            // Optional: Reverse text when the user presses 'Enter' or submits
            inputElement.addEventListener('keydown', function(event) {
                if (event.key === 'Enter') {
                    const reversedValue = inputElement.value.split('').reverse().join('');
                    inputElement.value = reversedValue;
                }
            });
        });
        
        document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('returnSearch');
    const suggestionsContainer = document.getElementById('returnSearchSuggestions');

    searchInput.addEventListener('input', function(event) {
        const inputText = event.target.value.trim();

        // Show the suggestions container when there is input
        if (inputText.length > 0) {
            suggestionsContainer.classList.remove('hidden'); // Show suggestions
            // Optionally, you can update the suggestions based on input here.
        } else {
            suggestionsContainer.classList.add('hidden'); // Hide suggestions
        }
    });
});

    </script>

    @vite(['resources/js/app.js'])
</x-app-layout>
