<x-app-layout>
    <x-title level="h2"
             size="2xl"
             weight="bold"
             icon="document-chart-bar"
             variant="s"
             iconStyle="plain"
             iconColor="gov-accent">Reports</x-title>

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <div class="py-8">
        <div class="sm:px-6 lg:px-8 space-y-10">
            {{-- Filters + Actions --}}
            <div class="shadow rounded-2xl p-6"
                 style="background: var(--card-bg); color: var(--text); border: 1px solid rgba(0,0,0,0.06);">
                <div class="grid grid-cols-1 lg:grid-cols-5 gap-4 items-end">

                    {{-- Report Type --}}
                    <div class="lg:col-span-2">
                        <x-input-label for="reportType" value="Report" />
                        <select id="reportType" class="mt-1 block w-full input-field">
                            @foreach($reports as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Period --}}
                    <div>
                        <x-input-label for="period" value="Period" />
                        <select id="period" class="mt-1 block w-full input-field">
                            <option value="week">This Week</option>
                            <option value="month" selected>This Month</option>
                            <option value="year">This Year</option>
                            <option value="custom">Custom</option>
                        </select>
                    </div>

                    {{-- Chart --}}
                    <div>
                        <x-input-label for="chartType" value="Chart" />
                        <select id="chartType" class="mt-1 block w-full input-field">
                            <option value="bar">Bar</option>
                            <option value="line">Line</option>
                            <option value="pie">Pie</option>
                            <option value="doughnut">Doughnut</option>
                        </select>
                    </div>

                   {{-- Generate Button --}}
<div class="lg:col-span-1 text-right">
    <x-primary-button id="generateBtn" class="w-full justify-center">
        {{-- Spinner (hidden until loading) --}}
        <svg id="genSpinner" class="hidden mr-2 animate-spin h-4 w-4 text-white"
             xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10"
                    stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor"
                  d="M4 12a8 8 0 018-8v8z"></path>
        </svg>

        {{-- Heroicon Play Icon --}}
        <x-heroicon-o-play class="h-4 w-4 mr-1" />

        <span>Generate</span>
    </x-primary-button>
</div>

                    {{-- Export Buttons --}}
                    <div class="flex gap-2 justify-end">
                        <x-secondary-button id="downloadPdfBtn" class="w-full justify-center">
                            <x-heroicon-o-document-text class="h-4 w-4 mr-1" /> PDF
                        </x-secondary-button>

                        <x-primary-button id="downloadXlsxBtn" class="bg-green-600 hover:bg-green-700 w-full justify-center">
                            <x-heroicon-o-table-cells class="h-4 w-4 mr-1" /> Excel
                        </x-primary-button>
                    </div>
                </div>

                {{-- Custom Range --}}
                <div id="customRangeRow" class="mt-4 hidden grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <x-input-label for="from" value="From" />
                        <x-text-input id="from" type="date" class="mt-1 w-full" />
                    </div>
                    <div>
                        <x-input-label for="to" value="To" />
                        <x-text-input id="to" type="date" class="mt-1 w-full" />
                    </div>
                    <div>
                        <x-input-label for="threshold" value="Low-stock threshold" />
                        <x-text-input id="threshold" type="number" min="1" value="5" class="mt-1 w-full" />
                    </div>
                </div>
            </div>

            {{-- Chart + Summary --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Chart --}}
                <div class="lg:col-span-2 shadow rounded-2xl p-6"
                     style="background: var(--card-bg); color: var(--text);">
                    <h3 id="reportTitle" class="text-lg font-bold mb-3" style="color: var(--text)">Report</h3>
                    <div class="w-full h-80 relative">
                        <div id="chartContainer" class="w-full h-full rounded"
                             style="background: var(--card-bg); color: var(--text);">
                            <canvas id="reportChart" class="w-full h-full"></canvas>
                            <div id="chartMessage" class="hidden absolute inset-0 flex items-center justify-center p-6">
                                <div class="text-center">
                                    <div id="chartMessageTitle" class="text-lg font-semibold" style="color: var(--text)"></div>
                                    <div id="chartMessageBody" class="text-sm mt-2" style="color: var(--muted)"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Summary --}}
                <div class="shadow rounded-2xl p-6" style="background: var(--card-bg); color: var(--text);">
                    <h4 class="text-md font-bold mb-3" style="color: var(--text)">Summary</h4>
                    <div id="summaryBoxes" class="space-y-3"></div>
                </div>
            </div>

            {{-- Table --}}
            <div class="shadow rounded-2xl p-6" style="background: var(--card-bg); color: var(--text);">
                <div class="overflow-auto">
                    <table id="reportTable" class="min-w-full rounded-lg text-sm" style="border-collapse: collapse; width:100%;">
                        <thead id="reportTableHead"
                               style="background: rgba(0,0,0,0.03); color: var(--text);">
                            <!-- dynamic headers inserted by JS -->
                        </thead>
                        <tbody id="reportTableBody" class="">
                            <!-- dynamic rows inserted by JS -->
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    {{-- expose routes for the JS module --}}
    <script>
        window.reportRoutes = {
            data: "{{ route('reports.data') }}",
            pdf: "{{ route('reports.export.pdf') }}",
            xlsx: "{{ route('reports.export.xlsx') }}"
        };
    </script>

    {{-- Vite entry (bundles app.js which imports reports.js) --}}
    @vite(['resources/js/app.js'])

    {{-- Table polish (theme-aware via CSS variables and theme class overrides) --}}
    <style>
        /* base spacing + readable text */
        #reportTable th, #reportTable td {
            padding: 0.75rem 1rem;
            text-align: left;
            white-space: nowrap;
            color: var(--text);
            border-bottom: 1px solid rgba(0,0,0,0.06);
        }

        /* head style */
        #reportTable thead th {
            font-weight: 700;
            background: rgba(0,0,0,0.03);
            color: var(--text);
        }

        /* even rows subtle */
        #reportTableBody tr:nth-child(even) { background-color: rgba(0,0,0,0.02); }
        #reportTableBody tr:hover { background-color: rgba(0,0,0,0.04); }

        /* dark theme overrides */
        .theme-dark #reportTable th,
        .theme-dark #reportTable td {
            border-bottom-color: rgba(255,255,255,0.06);
            color: var(--text);
        }
        .theme-dark #reportTable thead th {
            background: rgba(255,255,255,0.03);
            color: var(--text);
        }
        .theme-dark #reportTableBody tr:nth-child(even) { background-color: rgba(255,255,255,0.02); }
        .theme-dark #reportTableBody tr:hover { background-color: rgba(255,255,255,0.04); }

        /* original theme specific adjustments (slightly warmer head for contrast) */
        .theme-original #reportTable thead th {
            background: rgba(11,74,119,0.06); /* subtle tint that reads on ORIGINAL palette */
            color: var(--nav-text);
        }
    </style>
</x-app-layout>
