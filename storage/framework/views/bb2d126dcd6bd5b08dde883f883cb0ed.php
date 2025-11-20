<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['message' => '', 'userName' => '', 'type' => 'returning']));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter((['message' => '', 'userName' => '', 'type' => 'returning']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<div class="welcome-card-wrapper mb-6 animate-fade-in">
    
    <div class="welcome-card-custom relative overflow-hidden rounded-xl shadow-lg p-5 transform transition-all duration-300 hover:shadow-xl"
         style="background: linear-gradient(to bottom right, #8b5cf6, #7c3aed, #6366f1) !important;">
        
        
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="welcome-circle welcome-circle-1"></div>
            <div class="welcome-circle welcome-circle-2"></div>
        </div>

        
        <div class="relative z-10">
            <div class="flex items-center gap-3">
                
                <?php if($type === 'new'): ?>
                    <div class="welcome-icon rounded-full p-2 flex-shrink-0 animate-bounce-soft" style="background: rgba(255,255,255,0.2); backdrop-filter: blur(10px);">
                        <svg class="w-5 h-5" style="color: white !important;" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2 10.5a1.5 1.5 0 113 0v6a1.5 1.5 0 01-3 0v-6zM6 10.333v5.43a2 2 0 001.106 1.79l.05.025A4 4 0 008.943 18h5.416a2 2 0 001.962-1.608l1.2-6A2 2 0 0015.56 8H12V4a2 2 0 00-2-2 1 1 0 00-1 1v.667a4 4 0 01-.8 2.4L6.8 7.933a4 4 0 00-.8 2.4z" />
                        </svg>
                    </div>
                <?php else: ?>
                    <div class="welcome-icon rounded-full p-2 flex-shrink-0 animate-pulse-soft" style="background: rgba(255,255,255,0.2); backdrop-filter: blur(10px);">
                        <svg class="w-5 h-5" style="color: white !important;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5" />
                        </svg>
                    </div>
                <?php endif; ?>
                
                
                <div class="flex-1 min-w-0">
                    <h2 class="text-xl md:text-2xl font-bold tracking-tight animate-slide-up" style="color: white !important;">
                        <?php echo e($message ?: 'Welcome!'); ?>

                    </h2>
                    
                    
                    <div class="flex items-center gap-2 mt-1 animate-slide-up animation-delay-100">
                        <svg class="w-4 h-4 flex-shrink-0" style="color: rgba(255,255,255,0.8) !important;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-sm" style="color: rgba(255,255,255,0.9) !important;">
                            <?php echo e(now()->format('l, F j, Y • g:i A')); ?>

                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Fade in animation */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes slideUp {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes scaleIn {
        from { opacity: 0; transform: scale(0.9); }
        to { opacity: 1; transform: scale(1); }
    }

    @keyframes bounceSoft {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-3px); }
    }

    @keyframes pulseSoft {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.8; }
    }

    @keyframes float {
        0%, 100% { transform: translate(0, 0) scale(1); }
        50% { transform: translate(20px, -20px) scale(1.05); }
    }

    .animate-fade-in {
        animation: fadeIn 0.5s ease-out;
    }

    .animate-slide-up {
        animation: slideUp 0.5s ease-out;
    }

    .animate-scale-in {
        animation: scaleIn 0.5s ease-out;
    }

    .animate-bounce-soft {
        animation: bounceSoft 2s ease-in-out infinite;
    }

    .animate-pulse-soft {
        animation: pulseSoft 2s ease-in-out infinite;
    }

    .animation-delay-100 {
        animation-delay: 0.1s;
        opacity: 0;
        animation-fill-mode: forwards;
    }

    /* Floating background circles */
    .welcome-circle {
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.1) !important;
        animation: float 15s infinite ease-in-out;
    }

    .welcome-circle-1 {
        width: 150px;
        height: 150px;
        top: -40px;
        right: -40px;
        animation-delay: 0s;
    }

    .welcome-circle-2 {
        width: 100px;
        height: 100px;
        bottom: -20px;
        left: -20px;
        animation-delay: 5s;
    }

    /* Force purple background and white text - override global card styles */
    .welcome-card-custom {
        background: linear-gradient(to bottom right, #8b5cf6, #7c3aed, #6366f1) !important;
    }

    .dark .welcome-card-custom {
        background: linear-gradient(to bottom right, #7c3aed, #6d28d9, #4f46e5) !important;
    }
</style>
<?php /**PATH /home/u928333042/domains/gsoibims-tagoloan.com/public_html/resources/views/components/welcome-card.blade.php ENDPATH**/ ?>