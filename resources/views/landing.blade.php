<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GSO-IBIMS | Welcome</title>
    <link rel="icon" href="{{ asset('images/logo2.png') }}" type="image/png">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen text-white" style="background-color:#3F1D7B;background-image:url('{{ asset('images/landing-bg.svg') }}');background-size:cover;background-position:center;background-repeat:no-repeat;">
    <div class="relative overflow-hidden">
        <div class="max-w-7xl mx-auto px-6 lg:px-12 py-20 flex flex-col lg:flex-row items-center gap-16">
            <div class="flex-1 space-y-8">
                <span class="inline-flex items-center px-4 py-1 rounded-full bg-white/10 uppercase tracking-wide text-xs font-semibold">
                    Government Services - IBIMS
                </span>
                <h1 class="text-4xl md:text-5xl font-bold leading-tight">
                    Streamline Your General Services Operations.
                </h1>
                <p class="text-lg text-white/90 leading-relaxed max-w-xl">
                    Efficiently manage item borrowing and manpower requests for government offices.
                    Automate encoding, track real-time availability, and keep every transaction auditable.
                    GSO-IBIMS keeps accountability, transparency, and citizen service at the center of your daily work.
                </p>

                <div class="flex flex-wrap gap-4">
                    <a href="{{ route('login') }}"
                       class="inline-flex items-center justify-center px-6 py-3 rounded-xl font-semibold bg-blue-500 text-white shadow-lg hover:bg-blue-600 hover:shadow-xl transition-all transform hover:-translate-y-0.5">
                        Get Started
                    </a>
                    <a href="{{ route('public.borrow-items') }}"
                       class="inline-flex items-center justify-center px-6 py-3 rounded-xl font-semibold bg-white/15 text-white shadow-lg hover:shadow-xl transition-all transform hover:-translate-y-0.5">
                        View Items
                    </a>
                </div>

                @if($featuredItems->isNotEmpty())
                    <div class="bg-white/10 border border-white/10 rounded-2xl p-6 backdrop-blur">
                        <p class="uppercase text-xs tracking-wide mb-3 text-white/70">Recently Added Assets</p>
                        <div class="flex flex-wrap gap-4">
                            @foreach($featuredItems as $item)
                                <div class="flex items-center gap-3">
                                    <img src="{{ $item->photo_url }}" alt="{{ $item->name }}" class="h-12 w-12 rounded-lg object-cover bg-white/10">
                                    <div>
                                        <p class="font-semibold">{{ $item->name }}</p>
                                        <p class="text-xs text-white/70">{{ $item->category_name }} - {{ $item->total_qty }} total</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <!-- <div class="flex-1 w-full flex justify-center">
                <div class="relative bg-white/5 border border-white/20 rounded-[2.5rem] h-[32rem] w-full max-w-sm lg:max-w-lg overflow-hidden shadow-2xl">
                    <div id="landingPageImagePlaceholder" class="w-full h-full flex items-center justify-center text-white/70 text-center px-6">
                        <img src="{{ asset('images/mayor.png') }}" class="h-full w-full center object-cover" alt="GSO Mobile">
                    </div>
                </div>
            </div> -->
            
        </div>
    </div>
</body>
</html>