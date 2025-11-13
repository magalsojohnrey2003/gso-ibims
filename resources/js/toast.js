// Toast notification system using Alpine.js
// Global Alpine component for toast notifications with progress bar and shake animation

// Alpine.js Toast Component
if (typeof window !== 'undefined') {
    document.addEventListener('alpine:init', () => {
        window.Alpine.data('globalToast', () => ({
            show: false,
            message: '',
            title: '',
            type: 'success',
            timer: null,
            shakeAnimation: false,

            init() {
                // Listen for global toast events
                window.addEventListener('toast', (event) => {
                    const { message, type, title } = event.detail || {};
                    this.showToast(message, type, title);
                });
            },

            showToast(message, type = 'success', title = null) {
                // Clear any existing timer
                if (this.timer) {
                    clearTimeout(this.timer);
                    this.timer = null;
                }

                // Reset shake animation
                this.shakeAnimation = false;

                // Set message and type
                const safeType = ['success', 'error', 'warning', 'info'].includes(type) ? type : 'info';
                this.type = safeType;
                this.message = String(message || '').trim() || 'Notification';
                
                // Default titles based on type
                const defaultTitles = {
                    success: 'Success',
                    error: 'Error',
                    warning: 'Warning',
                    info: 'Info'
                };
                this.title = title || defaultTitles[safeType] || 'Notification';

                // Show toast
                this.show = true;

                // Reset and start progress bar animation
                this.$nextTick(() => {
                    const progressBar = this.$refs.progressbar;
                    if (progressBar) {
                        // Reset progress bar
                        progressBar.style.transition = 'none';
                        progressBar.style.width = '100%';
                        
                        // Start animation after a small delay
                        setTimeout(() => {
                            progressBar.style.transition = 'width 5000ms linear';
                            progressBar.style.width = '0%';
                        }, 50);
                    }

                    // Trigger shake animation for errors
                    if (safeType === 'error') {
                        this.shakeAnimation = true;
                        setTimeout(() => {
                            this.shakeAnimation = false;
                        }, 500);
                    }
                });

                // Auto-dismiss after 5 seconds
                this.timer = setTimeout(() => {
                    this.close();
                }, 5000);
            },

            close() {
                this.show = false;
                if (this.timer) {
                    clearTimeout(this.timer);
                    this.timer = null;
                }
            },

            pauseTimer() {
                if (this.timer) {
                    clearTimeout(this.timer);
                    this.timer = null;
                }
                // Pause progress bar animation
                const progressBar = this.$refs.progressbar;
                if (progressBar) {
                    const computedStyle = window.getComputedStyle(progressBar);
                    const currentWidth = computedStyle.width;
                    progressBar.style.transition = 'none';
                    progressBar.style.width = currentWidth;
                }
            },

            resumeTimer() {
                const progressBar = this.$refs.progressbar;
                if (progressBar) {
                    const currentWidth = parseFloat(progressBar.style.width);
                    const remainingPercentage = currentWidth;
                    const remainingTime = (remainingPercentage / 100) * 5000;
                    
                    if (remainingTime > 0) {
                        progressBar.style.transition = `width ${remainingTime}ms linear`;
                        progressBar.style.width = '0%';
                        
                        this.timer = setTimeout(() => {
                            this.close();
                        }, remainingTime);
                    }
                }
            }
        }));
    });
}

// Global helper function to trigger toast
export default function showToast(message = '', type = 'success', title = null) {
    if (typeof window === 'undefined') {
        return;
    }
    
    // Dispatch custom event that Alpine component will listen for
    window.dispatchEvent(new CustomEvent('toast', {
        detail: { message, type, title }
    }));
}

// Expose to window for global access
if (typeof window !== 'undefined') {
    window.showToast = showToast;
}


