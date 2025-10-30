class LocationSelector {
    constructor(options = {}) {
        this.municipalitySelect = document.getElementById(options.municipalityId || 'location_municipality');
        this.barangaySelect = document.getElementById(options.barangayId || 'location_barangay');
        this.purokSelect = document.getElementById(options.purokId || 'location_purok');
        this.hiddenInput = document.getElementById(options.hiddenFieldId || 'location');
        this.displayField = document.getElementById(options.displayFieldId || 'location_display');
        this.displayWrapper = document.getElementById(options.displayWrapperId || 'locationDisplayWrapper');

        this.barangaysUrl = options.barangaysUrl;
        this.puroksUrl = options.puroksUrl;

        this.initialMunicipality = this.municipalitySelect?.dataset.initial || '';
        this.initialBarangay = this.barangaySelect?.dataset.initial || '';
        this.initialPurok = this.purokSelect?.dataset.initial || '';
    }

    init() {
        if (!this.municipalitySelect || !this.barangaySelect || !this.purokSelect || !this.hiddenInput || !this.displayField) {
            return;
        }

        this.bindEvents();

        if (this.initialMunicipality) {
            this.municipalitySelect.value = this.initialMunicipality;
            this.loadBarangays(this.initialMunicipality).then(() => {
                if (this.initialBarangay) {
                    this.barangaySelect.value = this.initialBarangay;
                    this.loadPuroks().then(() => {
                        if (this.initialPurok) {
                            this.purokSelect.value = this.initialPurok;
                        }
                        this.updateLocationValue();
                    });
                }
            });
        } else {
            this.resetSelect(this.barangaySelect, 'Select barangay');
            this.resetSelect(this.purokSelect, 'Select purok / zone / sitio');
            this.updateLocationValue();
        }
    }

    bindEvents() {
        this.municipalitySelect.addEventListener('change', () => {
            const value = this.municipalitySelect.value;
            this.resetSelect(this.barangaySelect, 'Loading barangays...', true);
            this.resetSelect(this.purokSelect, 'Select purok / zone / sitio', true);
            this.hiddenInput.value = '';
            this.displayField.value = '';
            this.toggleDisplay(false);
            if (value) {
                this.loadBarangays(value);
            } else {
                this.resetSelect(this.barangaySelect, 'Select barangay', true);
            }
        });

        this.barangaySelect.addEventListener('change', () => {
            this.resetSelect(this.purokSelect, 'Loading puroks...', true);
            this.hiddenInput.value = '';
            this.displayField.value = '';
            this.toggleDisplay(false);
            if (this.barangaySelect.value) {
                this.loadPuroks();
            } else {
                this.resetSelect(this.purokSelect, 'Select purok / zone / sitio', true);
                this.updateLocationValue();
            }
        });

        this.purokSelect.addEventListener('change', () => {
            this.updateLocationValue();
        });
    }

    resetSelect(select, placeholder, disable = false) {
        if (!select) return;
        select.innerHTML = '';
        const option = document.createElement('option');
        option.value = '';
        option.textContent = placeholder;
        option.disabled = true;
        option.selected = true;
        select.appendChild(option);
        select.disabled = disable;
    }

    async loadBarangays(municipalitySlug) {
        if (!this.barangaysUrl) return;
        try {
            const url = new URL(this.barangaysUrl, window.location.origin);
            url.searchParams.set('municipality', municipalitySlug);
            const response = await fetch(url.toString(), { headers: { 'Accept': 'application/json' } });
            if (!response.ok) {
                throw new Error(`Barangay request failed (${response.status})`);
            }
            const data = await response.json();
            const barangays = Array.isArray(data.barangays) ? data.barangays : [];

            this.resetSelect(this.barangaySelect, 'Select barangay');
            barangays.forEach((name) => {
                const option = document.createElement('option');
                option.value = name;
                option.textContent = name;
                this.barangaySelect.appendChild(option);
            });
            this.barangaySelect.disabled = false;
            this.updateLocationValue();
        } catch (error) {
            console.error('Failed to load barangays', error);
            this.resetSelect(this.barangaySelect, 'Unable to load barangays', true);
            window.dispatchEvent(new CustomEvent('location:updated', { detail: { valid: false, error: 'barangay' } }));
        }
    }

    async loadPuroks() {
        if (!this.puroksUrl) {
            this.resetSelect(this.purokSelect, 'Select purok / zone / sitio', true);
            return;
        }

        const municipalityLabel = this.getSelectedMunicipalityLabel();
        const barangay = this.barangaySelect.value;
        if (!municipalityLabel || !barangay) {
            this.resetSelect(this.purokSelect, 'Select purok / zone / sitio', true);
            return;
        }

        try {
            const url = new URL(this.puroksUrl, window.location.origin);
            url.searchParams.set('municipality', municipalityLabel);
            url.searchParams.set('barangay', barangay);
            const response = await fetch(url.toString(), { headers: { 'Accept': 'application/json' } });
            if (!response.ok) {
                throw new Error(`Purok request failed (${response.status})`);
            }
            const data = await response.json();
            const puroks = Array.isArray(data.puroks) ? data.puroks : [];

            this.resetSelect(this.purokSelect, 'Select purok / zone / sitio');
            puroks.forEach((name) => {
                const option = document.createElement('option');
                option.value = name;
                option.textContent = name;
                this.purokSelect.appendChild(option);
            });
            this.purokSelect.disabled = false;
            this.updateLocationValue();
        } catch (error) {
            console.error('Failed to load puroks', error);
            this.resetSelect(this.purokSelect, 'Unable to load puroks', true);
            window.dispatchEvent(new CustomEvent('location:updated', { detail: { valid: false, error: 'purok' } }));
        }
    }

    getSelectedMunicipalityLabel() {
        const option = this.municipalitySelect?.selectedOptions?.[0];
        return option ? (option.dataset.label || option.textContent || option.value) : '';
    }

    updateLocationValue() {
        const municipalityLabel = this.getSelectedMunicipalityLabel();
        const barangay = this.barangaySelect.value;
        const purok = this.purokSelect.value;

        if (municipalityLabel && barangay && purok) {
            const formatted = `${municipalityLabel}, ${barangay}, ${purok}`;
            this.hiddenInput.value = formatted;
            this.displayField.value = formatted;
            this.toggleDisplay(true);
            window.dispatchEvent(new CustomEvent('location:updated', { detail: { valid: true, value: formatted } }));
        } else {
            this.hiddenInput.value = '';
            this.displayField.value = '';
            this.toggleDisplay(false);
            window.dispatchEvent(new CustomEvent('location:updated', { detail: { valid: false } }));
        }
    }

    toggleDisplay(show) {
        if (!this.displayWrapper) return;
        this.displayWrapper.classList.toggle('hidden', !show);
    }
}

export default function initLocationSelector(options = {}) {
    const selector = new LocationSelector(options);
    selector.init();
    return selector;
}
