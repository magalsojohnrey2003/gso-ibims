<x-modal name="view-item-history" maxWidth="2xl">
    <div class="w-full bg-white shadow-xl overflow-hidden flex flex-col max-h-[85vh]" 
         x-data="{
            instanceId: null,
            propertyNumber: '',
            events: [],
            loading: false,
            error: '',
            historyListener: null,
            async openFor(id, pn) {
                this.instanceId = id;
                this.propertyNumber = pn || '';
                this.events = [];
                this.error = '';
                this.loading = true;
                try {
                    const res = await fetch(`/admin/items/instances/${id}/history`, { headers: { Accept: 'application/json' } });
                    const data = await res.json().catch(() => null);
                    if (!res.ok) throw new Error(data?.message || 'Failed to load history');
                    this.propertyNumber = data?.property_number || pn || '';
                        const rawEvents = Array.isArray(data?.events) ? data.events : [];
                        this.events = rawEvents.map((evt) => {
                        const meta = this.getActionMeta(evt.action);
                        const chips = this.buildChips(evt);
                        const metrics = this.partitionChips(chips);
                        const { others, ...metricValues } = metrics;
                            const conditionMeta = this.getConditionMeta(metricValues.condition);
                            const dateLabel = this.getDateCardLabel(metricValues.condition);
                        return {
                            ...evt,
                            actionLabel: this.formatAction(evt.action),
                            formattedAt: this.formatTimestamp(evt.performed_at),
                            meta,
                            metrics: metricValues,
                            conditionMeta,
                            dateLabel,
                            additionalChips: others,
                        };
                    });
                } catch (e) {
                    this.error = e?.message || 'Failed to load history';
                } finally {
                    this.loading = false;
                }
            },
            formatAction(action) {
                return String(action || 'Info')
                    .toLowerCase()
                    .split(/[_\s]+/)
                    .filter(Boolean)
                    .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
                    .join(' ') || 'Info';
            },
            formatTimestamp(value) {
                if (!value) return '—';
                try {
                    const date = new Date(value);
                    if (Number.isNaN(date.getTime())) return String(value);
                    return date.toLocaleString('en-US', { dateStyle: 'medium', timeStyle: 'short' });
                } catch (error) {
                    return String(value);
                }
            },
            formatCondition(value) {
                if (!value) return '—';
                const str = String(value).trim();
                if (!str) return '—';
                return str
                    .replace(/[_\s]+/g, ' ')
                    .split(' ')
                    .filter(Boolean)
                    .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
                    .join(' ');
            },
            getActionMeta(action) {
                const key = String(action || 'info').toLowerCase();
                const ACTION_META = {
                    created: {
                        icon: 'fa-plus',
                        gradient: 'from-emerald-500 via-emerald-500 to-emerald-400',
                        badge: 'bg-emerald-100 text-emerald-700',
                    },
                    borrowed: {
                        icon: 'fa-box',
                        gradient: 'from-indigo-500 via-indigo-500 to-indigo-400',
                        badge: 'bg-indigo-100 text-indigo-700',
                    },
                    returned: {
                        icon: 'fa-arrow-left',
                        gradient: 'from-sky-500 via-sky-500 to-sky-400',
                        badge: 'bg-sky-100 text-sky-700',
                    },
                    damaged: {
                        icon: 'fa-exclamation-triangle',
                        gradient: 'from-rose-500 via-rose-500 to-rose-400',
                        badge: 'bg-rose-100 text-rose-700',
                    },
                    minor_damage: {
                        icon: 'fa-exclamation-circle',
                        gradient: 'from-amber-500 via-amber-500 to-amber-400',
                        badge: 'bg-amber-100 text-amber-700',
                    },
                    missing: {
                        icon: 'fa-question-circle',
                        gradient: 'from-orange-500 via-orange-500 to-orange-400',
                        badge: 'bg-orange-100 text-orange-700',
                    },
                    not_received: {
                        icon: 'fa-triangle-exclamation',
                        gradient: 'from-orange-500 via-orange-500 to-orange-400',
                        badge: 'bg-orange-100 text-orange-700',
                    },
                };
                return ACTION_META[key] || {
                    icon: 'fa-info',
                    gradient: 'from-slate-500 via-slate-500 to-slate-400',
                    badge: 'bg-slate-100 text-slate-700',
                };
            },
            buildChips(evt) {
                const chips = [];
                const payload = evt?.payload || {};
                const fields = {
                    borrower: 'Borrower',
                    borrower_name: 'Borrower',
                    name: 'Borrower',
                    location: 'Location',
                    destination: 'Destination',
                    condition: 'Item Condition',
                    item_condition: 'Item Condition',
                    return_id: 'Return Ref',
                    date_returned: 'Date Returned',
                    returned_at: 'Date Returned',
                    return_date: 'Date Returned',
                    remarks: 'Remarks',
                    reason: 'Reason',
                };
                Object.entries(fields).forEach(([key, label]) => {
                    const value = payload?.[key];
                    if (value !== undefined && value !== null && String(value).trim() !== '') {
                        chips.push({ key, label, value: Array.isArray(value) ? value.join(', ') : String(value) });
                    }
                });
                return chips;
            },
            partitionChips(chips) {
                const result = {
                    borrower: '—',
                    condition: '—',
                    returned: '—',
                    others: [],
                };

                chips.forEach((chip) => {
                    const key = String(chip.key || '').toLowerCase();
                    if (['borrower', 'borrower_name', 'name'].includes(key) && result.borrower === '—') {
                        const str = String(chip.value || '').trim();
                        result.borrower = str || '—';
                        return;
                    }
                    if (['condition', 'item_condition'].includes(key) && result.condition === '—') {
                        result.condition = this.formatCondition(chip.value);
                        return;
                    }
                    if (['date_returned', 'returned_at', 'return_date'].includes(key) && result.returned === '—') {
                        result.returned = this.formatTimestamp(chip.value);
                        return;
                    }
                    result.others.push(chip);
                });

                return result;
            },
            getConditionMeta(label) {
                const key = String(label || '').toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
                const MAP = {
                    good: {
                        container: 'flex items-start gap-3 rounded-2xl border border-emerald-100 bg-emerald-50/60 px-3 py-3',
                        iconBg: 'flex h-9 w-9 items-center justify-center rounded-full bg-emerald-200 text-emerald-700',
                        icon: 'fa-check',
                        titleClass: 'text-[0.7rem] uppercase tracking-wide text-emerald-700 font-semibold',
                        valueClass: 'text-sm font-medium text-emerald-900'
                    },
                    minor_damage: {
                        container: 'flex items-start gap-3 rounded-2xl border border-amber-100 bg-amber-50/60 px-3 py-3',
                        iconBg: 'flex h-9 w-9 items-center justify-center rounded-full bg-amber-200 text-amber-700',
                        icon: 'fa-exclamation-circle',
                        titleClass: 'text-[0.7rem] uppercase tracking-wide text-amber-700 font-semibold',
                        valueClass: 'text-sm font-medium text-amber-900'
                    },
                    damage: {
                        container: 'flex items-start gap-3 rounded-2xl border border-rose-100 bg-rose-50/60 px-3 py-3',
                        iconBg: 'flex h-9 w-9 items-center justify-center rounded-full bg-rose-200 text-rose-700',
                        icon: 'fa-exclamation-triangle',
                        titleClass: 'text-[0.7rem] uppercase tracking-wide text-rose-700 font-semibold',
                        valueClass: 'text-sm font-medium text-rose-900'
                    },
                    missing: {
                        container: 'flex items-start gap-3 rounded-2xl border border-rose-100 bg-rose-50/60 px-3 py-3',
                        iconBg: 'flex h-9 w-9 items-center justify-center rounded-full bg-rose-200 text-rose-700',
                        icon: 'fa-question-circle',
                        titleClass: 'text-[0.7rem] uppercase tracking-wide text-rose-700 font-semibold',
                        valueClass: 'text-sm font-medium text-rose-900'
                    },
                    not_received: {
                        container: 'flex items-start gap-3 rounded-2xl border border-rose-100 bg-rose-50/60 px-3 py-3',
                        iconBg: 'flex h-9 w-9 items-center justify-center rounded-full bg-rose-200 text-rose-700',
                        icon: 'fa-triangle-exclamation',
                        titleClass: 'text-[0.7rem] uppercase tracking-wide text-rose-700 font-semibold',
                        valueClass: 'text-sm font-medium text-rose-900'
                    }
                };
                return MAP[key] || {
                    container: 'flex items-start gap-3 rounded-2xl border border-slate-100 bg-slate-50/60 px-3 py-3',
                    iconBg: 'flex h-9 w-9 items-center justify-center rounded-full bg-slate-200 text-slate-700',
                    icon: 'fa-clipboard-check',
                    titleClass: 'text-[0.7rem] uppercase tracking-wide text-slate-700 font-semibold',
                    valueClass: 'text-sm font-medium text-slate-900'
                };
            },
            getDateCardLabel(condition) {
                const key = String(condition || '').toLowerCase();
                if (!key) return 'Date Returned';
                // For Good or Minor Damage -> Date Returned, for Damaged or Missing -> Date Reported
                if (key.includes('good') || key.includes('minor')) return 'Date Returned';
                if (key.includes('damage') || key.includes('missing') || key.includes('damaged') || key.includes('not_received')) return 'Date Reported';
                return 'Date Returned';
            },
            
         }"
             x-init="
                historyListener = (e) => {
                    const id = e.detail?.instanceId;
                    const pn = e.detail?.propertyNumber;
                    if (!id) return;
                    $dispatch('open-modal', 'view-item-history');
                    $nextTick(() => {
                        $data.openFor(id, pn);
                    });
                };
                window.addEventListener('item-history:open', historyListener);
             "
    >
        <div class="bg-indigo-600 text-white px-6 py-5 sticky top-0 z-20">
            <h3 class="text-2xl font-bold flex items-center gap-2">
                <i class="fas fa-history"></i>
                <span>History</span>
            </h3>
            <p class="text-indigo-100 mt-1 text-sm leading-relaxed">
                Property Number: <strong x-text="propertyNumber || '—'"></strong>
            </p>
        </div>
        <div class="flex-1 overflow-y-auto p-6">
            <template x-if="loading">
                <div class="flex items-center justify-center py-16 text-gray-500">
                    <i class="fas fa-circle-notch fa-spin mr-2"></i>
                    Loading history...
                </div>
            </template>
            <template x-if="!loading && error">
                <div class="rounded-md bg-red-50 p-4 text-red-700" x-text="error"></div>
            </template>
            <template x-if="!loading && !error && events.length === 0">
                <div class="rounded-md bg-gray-50 p-4 text-gray-600">No history found for this property number.</div>
            </template>
            <div x-show="!loading && !error && events.length > 0" class="relative">
                <div class="relative ml-3 sm:ml-6">
                    <div class="absolute left-1 sm:left-2 top-0 bottom-0 w-0.5 bg-gradient-to-b from-indigo-200 via-indigo-200 to-transparent"></div>
                    <div class="space-y-6">
                        <template x-for="evt in events" :key="evt.id">
                            <div class="relative pl-12 sm:pl-16">
                                <div class="absolute left-[-0.2rem] sm:left-[-0.25rem] top-1">
                                    <div class="h-12 w-12 rounded-full bg-gradient-to-br flex items-center justify-center text-white shadow-lg ring-4 ring-white"
                                         :class="evt.meta.gradient">
                                        <i class="fas text-base" :class="evt.meta.icon"></i>
                                    </div>
                                </div>
                                <div class="ml-1 sm:ml-2 rounded-3xl border border-slate-100 bg-white shadow-xl overflow-hidden">
                                    <div class="border-b border-slate-100 bg-slate-50/70 px-5 py-3 flex flex-wrap items-center justify-between gap-3">
                                        <div class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold"
                                             :class="evt.meta.badge">
                                            <i class="fas" :class="evt.meta.icon"></i>
                                            <span x-text="evt.actionLabel"></span>
                                        </div>
                                        <span class="text-xs font-medium text-slate-500" x-text="evt.formattedAt"></span>
                                    </div>
                                    <div class="p-5 space-y-4">
                                        <div class="grid gap-3 sm:grid-cols-3">
                                            <div class="flex items-start gap-3 rounded-2xl border border-indigo-100 bg-indigo-50/60 px-3 py-3">
                                                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-indigo-200 text-indigo-700">
                                                    <i class="fas fa-user"></i>
                                                </div>
                                                <div>
                                                    <p class="text-[0.7rem] uppercase tracking-wide text-indigo-700 font-semibold">Borrower</p>
                                                    <p class="text-sm font-medium text-indigo-900" x-text="evt.metrics.borrower"></p>
                                                </div>
                                            </div>
                                            <div :class="evt.conditionMeta.container">
                                                <div :class="evt.conditionMeta.iconBg">
                                                    <i class="fas" :class="evt.conditionMeta.icon"></i>
                                                </div>
                                                <div>
                                                    <p :class="evt.conditionMeta.titleClass">Item Condition</p>
                                                    <p :class="evt.conditionMeta.valueClass" x-text="evt.metrics.condition"></p>
                                                </div>
                                            </div>
                                            <div class="flex items-start gap-3 rounded-2xl border border-emerald-100 bg-emerald-50/60 px-3 py-3">
                                                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-emerald-200 text-emerald-700">
                                                        <i class="fas fa-calendar-check"></i>
                                                    </div>
                                                    <div>
                                                        <p class="text-[0.7rem] uppercase tracking-wide text-emerald-700 font-semibold" x-text="evt.dateLabel"></p>
                                                        <p class="text-sm font-medium text-emerald-900" x-text="evt.metrics.returned"></p>
                                                    </div>
                                            </div>
                                        </div>
                                        <template x-if="evt.actor">
                                            <div class="flex items-center gap-2 text-sm text-slate-600">
                                                <span class="font-semibold text-slate-700">Handled by</span>
                                                <span class="text-slate-800" x-text="evt.actor"></span>
                                            </div>
                                        </template>
                                        <template x-if="evt.additionalChips.length">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <template x-for="chip in evt.additionalChips" :key="chip.key">
                                                    <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 text-slate-700 px-3 py-1 text-xs font-medium">
                                                        <span class="font-semibold" x-text="chip.label + ':'"></span>
                                                        <span x-text="chip.value"></span>
                                                    </span>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
        <div class="px-6 py-4 border-t bg-white sticky bottom-0 z-20 flex justify-end">
            <x-button variant="secondary" iconName="x-mark" type="button" x-on:click="$dispatch('close-modal', 'view-item-history')">Close</x-button>
        </div>
    </div>
</x-modal>
