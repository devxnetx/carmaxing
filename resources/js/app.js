import Alpine from 'alpinejs';
import flatpickr from 'flatpickr';
import { Bulgarian } from 'flatpickr/dist/esm/l10n/bg.js';
import 'flatpickr/dist/flatpickr.min.css';
import '../css/datepicker.css';
import Quill from 'quill';
import 'quill/dist/quill.snow.css';
import '../css/quill.css';

window.Alpine = Alpine;

function functionalCookiesAllowed() {
    return document.documentElement.dataset.functionalCookies === '1';
}

function persistThemeCookie(theme) {
    if (!functionalCookiesAllowed()) {
        return;
    }

    document.cookie = `theme=${theme};path=/;max-age=31536000;SameSite=Lax`;
}

Alpine.data('themeToggle', () => ({
    theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light',
    toggle() {
        this.theme = this.theme === 'light' ? 'dark' : 'light';
        document.documentElement.classList.toggle('dark', this.theme === 'dark');
        persistThemeCookie(this.theme);
    },
}));

Alpine.data('cookieConsent', (initialConsent, storeUrl, policyUrl) => ({
    consent: initialConsent,
    preferences: {
        functional: initialConsent.functional,
        analytics: initialConsent.analytics,
        marketing: initialConsent.marketing,
    },
    visible: !initialConsent.hasChoice,
    customizing: false,
    saving: false,
    policyUrl,

    openSettings() {
        this.preferences = {
            functional: this.consent.functional,
            analytics: this.consent.analytics,
            marketing: this.consent.marketing,
        };
        this.customizing = true;
        this.visible = true;
    },

    async persist(choice, extra = {}) {
        this.saving = true;

        try {
            const response = await fetch(storeUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    Accept: 'application/json',
                },
                body: JSON.stringify({ choice, ...extra }),
            });

            if (!response.ok) {
                return;
            }

            const data = await response.json();
            this.consent = data.consent;
            this.preferences = {
                functional: this.consent.functional,
                analytics: this.consent.analytics,
                marketing: this.consent.marketing,
            };
            document.documentElement.dataset.functionalCookies = this.consent.functional ? '1' : '0';

            if (!this.consent.functional) {
                document.cookie = 'theme=;path=/;max-age=0;SameSite=Lax';
                document.cookie = 'locale=;path=/;max-age=0;SameSite=Lax';
            } else {
                persistThemeCookie(document.documentElement.classList.contains('dark') ? 'dark' : 'light');
            }

            this.customizing = false;
            this.visible = false;
        } finally {
            this.saving = false;
        }
    },

    acceptAll() {
        this.persist('all');
    },

    rejectNonEssential() {
        this.persist('essential');
    },

    saveCustom() {
        this.persist('custom', { ...this.preferences });
    },
}));

Alpine.data('brandModelPicker', (brandId = null, selectedSeries = [], selectedModels = [], scope = 'listings') => ({
    brandId: brandId ? String(brandId) : '',
    scope,
    series: [],
    flatModels: [],
    selectedSeries: normalizeIdList(selectedSeries),
    selectedModels: normalizeIdList(selectedModels),
    loading: false,

    get flattenedTree() {
        return this.flattenNodes(this.series, 0);
    },

    async init() {
        if (this.brandId) {
            await this.loadModels(this.brandId);
        }
    },

    async loadModels(id) {
        this.brandId = id ? String(id) : '';
        if (!id) {
            this.series = [];
            this.flatModels = [];
            return;
        }

        this.loading = true;
        const res = await fetch(`/api/brands/${id}/models?scope=${encodeURIComponent(this.scope)}`);
        const data = await res.json();
        this.series = data.series || [];
        this.flatModels = data.flat_models || [];
        this.loading = false;
    },

    flattenNodes(nodes, depth) {
        const result = [];

        for (const node of nodes) {
            result.push({ node, depth });

            if (node.children?.length) {
                result.push(...this.flattenNodes(node.children, depth + 1));
            }
        }

        return result;
    },

    descendantModelIds(node) {
        const ids = [];

        if (!node.children?.length) {
            if (node.type === 'model') {
                ids.push(Number(node.id));
            }

            return ids;
        }

        for (const child of node.children) {
            ids.push(...this.descendantModelIds(child));
        }

        return ids;
    },

    hasSeriesId(id) {
        return this.selectedSeries.includes(Number(id));
    },

    hasModelId(id) {
        return this.selectedModels.includes(Number(id));
    },

    toggleSeries(seriesId, childIds) {
        const normalizedId = Number(seriesId);
        const normalizedChildIds = childIds.map(Number);
        const idx = this.selectedSeries.indexOf(normalizedId);

        if (idx >= 0) {
            this.selectedSeries.splice(idx, 1);
            normalizedChildIds.forEach((id) => {
                const mi = this.selectedModels.indexOf(id);
                if (mi >= 0) {
                    this.selectedModels.splice(mi, 1);
                }
            });
        } else {
            this.selectedSeries.push(normalizedId);
            normalizedChildIds.forEach((id) => {
                if (!this.selectedModels.includes(id)) {
                    this.selectedModels.push(id);
                }
            });
        }
    },

    toggleModel(modelId) {
        const normalizedId = Number(modelId);
        const idx = this.selectedModels.indexOf(normalizedId);

        if (idx >= 0) {
            this.selectedModels.splice(idx, 1);
        } else {
            this.selectedModels.push(normalizedId);
        }
    },
}));

function normalizeIdList(values) {
    return [...new Set((values || []).map(Number).filter((id) => !Number.isNaN(id) && id > 0))];
}

Alpine.data('listingBrandModel', (initialBrandId = null, initialModelId = null) => ({
    brandId: initialBrandId ? String(initialBrandId) : '',
    modelId: initialModelId ? String(initialModelId) : '',
    series: [],
    flatModels: [],
    loading: false,

    async init() {
        if (this.brandId) {
            await this.loadModels(this.brandId, false);
        }
    },

    async loadModels(id, resetModel = true) {
        this.brandId = id ? String(id) : '';

        if (!id) {
            this.series = [];
            this.flatModels = [];
            if (resetModel) {
                this.modelId = '';
            }
            return;
        }

        if (resetModel) {
            this.modelId = '';
        }

        this.loading = true;

        try {
            const res = await fetch(`/api/brands/${id}/models`);
            const data = await res.json();
            this.series = data.series || [];
            this.flatModels = data.flat_models || [];
        } finally {
            this.loading = false;
        }
    },
}));

Alpine.data('listingFormWizard', (config) => ({
    step: config.initialStep || 0,
    labels: config.labels,
    done: config.existing ? config.labels.map(() => true) : config.labels.map(() => false),

    next() {
        if (!this.validateStep(this.step, true)) {
            return;
        }

        this.done[this.step] = true;
        this.step += 1;
        this.scrollUp();
    },

    back() {
        if (this.step === 0) {
            return;
        }

        this.step -= 1;
        this.scrollUp();
    },

    go(index) {
        if (index === this.step) {
            return;
        }

        if (index > this.step) {
            for (let current = this.step; current < index; current += 1) {
                if (!this.validateStep(current, true)) {
                    this.step = current;
                    return;
                }

                this.done[current] = true;
            }
        }

        this.step = index;
        this.scrollUp();
    },

    submitForm(event) {
        for (let current = 0; current < this.labels.length; current += 1) {
            if (!this.validateStep(current, false)) {
                event.preventDefault();
                this.step = current;
                this.$nextTick(() => this.validateStep(current, true));
                this.scrollUp();
                return;
            }

            this.done[current] = true;
        }
    },

    validateStep(index, report) {
        const section = this.$root.querySelector(`[data-step="${index}"]`);

        if (!section) {
            return true;
        }

        const fields = [...section.querySelectorAll('input, select, textarea')].filter((field) => {
            return field.type !== 'hidden' && !field.closest('[data-skip-validation]');
        });

        for (const field of fields) {
            if (field.disabled) {
                if (field.required && field.value === '') {
                    if (report) {
                        field.disabled = false;
                        field.reportValidity();
                        field.disabled = true;
                    }

                    return false;
                }

                continue;
            }

            if (!field.checkValidity()) {
                if (report) {
                    field.reportValidity();
                }

                return false;
            }
        }

        return true;
    },

    scrollUp() {
        this.$nextTick(() => {
            this.$root.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    },
}));

Alpine.data('listingPhotos', (existing) => ({
    items: existing.map((image) => ({
        kind: 'saved',
        id: image.id,
        url: image.url,
        name: '',
    })),

    addFiles(event) {
        [...(event.target.files || [])].forEach((file) => {
            if (this.items.length >= 20) {
                return;
            }

            this.items.push({
                kind: 'new',
                file,
                url: URL.createObjectURL(file),
                name: file.name,
            });
        });

        event.target.value = '';
        this.syncFiles();
    },

    remove(index) {
        const item = this.items[index];

        if (item?.kind === 'new') {
            URL.revokeObjectURL(item.url);
        }

        this.items.splice(index, 1);
        this.syncFiles();
    },

    move(index, direction) {
        const target = index + direction;

        if (target < 0 || target >= this.items.length) {
            return;
        }

        const [item] = this.items.splice(index, 1);
        this.items.splice(target, 0, item);
        this.syncFiles();
    },

    token(item) {
        if (item.kind === 'saved') {
            return `e-${item.id}`;
        }

        return `n-${this.items.filter((entry) => entry.kind === 'new').indexOf(item)}`;
    },

    syncFiles() {
        const transfer = new DataTransfer();

        this.items.filter((item) => item.kind === 'new').forEach((item) => {
            transfer.items.add(item.file);
        });

        if (this.$refs.files) {
            this.$refs.files.files = transfer.files;
        }
    },
}));

function isoToDate(value) {
    const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value || '');

    if (!match) {
        return null;
    }

    return new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]));
}

Alpine.data('datePicker', (initial, locale) => ({
    picker: null,
    hasValue: Boolean(initial),
    closing: false,

    init() {
        this.picker = flatpickr(this.$refs.display, {
            defaultDate: isoToDate(initial),
            dateFormat: 'd.m.Y',
            allowInput: window.matchMedia('(pointer: fine)').matches,
            disableMobile: true,
            clickOpens: true,
            monthSelectorType: 'dropdown',
            locale: locale === 'bg' ? Bulgarian : { firstDayOfWeek: 1 },
            minDate: new Date(1950, 0, 1),
            appendTo: document.body,
            onChange: (dates) => this.write(dates),
            onClose: () => this.commitTyped(),
        });
    },

    write(dates) {
        const value = dates.length ? flatpickr.formatDate(dates[0], 'Y-m-d') : '';
        this.$refs.value.value = value;
        this.hasValue = value !== '';
    },

    commitTyped() {
        if (this.closing) {
            return;
        }

        this.closing = true;
        const typed = this.$refs.display.value.trim();

        if (typed === '') {
            if (this.picker.selectedDates.length) {
                this.picker.clear();
            }

            this.$refs.display.value = '';
            this.write([]);
            this.closing = false;
            return;
        }

        const parsed = this.picker.parseDate(typed, 'd.m.Y');

        if (!parsed || parsed.getFullYear() < 1950) {
            this.restore();
            this.closing = false;
            return;
        }

        const iso = flatpickr.formatDate(parsed, 'Y-m-d');

        if (iso !== this.$refs.value.value) {
            this.picker.setDate(parsed, true);
        }

        this.closing = false;
    },

    restore() {
        const current = isoToDate(this.$refs.value.value);

        if (current) {
            this.picker.setDate(current, false);
            return;
        }

        this.picker.clear();
        this.$refs.display.value = '';
        this.write([]);
    },

    open() {
        this.picker?.open();
    },

    clear() {
        this.picker?.clear();
        this.write([]);
    },

    destroy() {
        this.picker?.destroy();
    },
}));

Alpine.data('richTextEditor', (initial, labels) => ({
    quill: null,

    init() {
        this.quill = new Quill(this.$refs.editor, {
            theme: 'snow',
            bounds: this.$el,
            modules: {
                toolbar: [
                    ['bold', 'italic', 'underline'],
                    [{ list: 'ordered' }, { list: 'bullet' }],
                    ['link'],
                    ['clean'],
                ],
            },
        });

        const toolbar = this.quill.getModule('toolbar').container;
        const title = (selector, label) => {
            toolbar.querySelectorAll(selector).forEach((button) => {
                button.setAttribute('aria-label', label);
                button.setAttribute('title', label);
            });
        };
        title('.ql-bold', labels.bold);
        title('.ql-italic', labels.italic);
        title('.ql-underline', labels.underline);
        title('.ql-list[value="ordered"]', labels.ordered);
        title('.ql-list[value="bullet"]', labels.bullet);
        title('.ql-link', labels.link);
        title('.ql-clean', labels.clear);

        if (initial) {
            this.quill.clipboard.dangerouslyPasteHTML(initial);
        }

        this.sync();
        this.quill.on('text-change', () => this.sync());
        this.$el.closest('form')?.addEventListener('submit', () => this.sync());
    },

    sync() {
        const html = this.quill.getSemanticHTML();
        this.$refs.input.value = html;
    },
}));

Alpine.data('locationPicker', (initial = {}, citiesEndpoint = 'cities') => ({
    regionId: initial.region_id ? String(initial.region_id) : '',
    city: initial.city || '',
    cities: [],
    loadingCities: false,
    citiesEndpoint,

    async init() {
        if (this.regionId) {
            await this.loadCities(this.regionId);
        }
    },

    async onRegionChange() {
        this.city = '';
        await this.loadCities(this.regionId);
    },

    async loadCities(regionId) {
        if (!regionId) {
            this.cities = [];
            return;
        }

        this.loadingCities = true;

        try {
            const response = await fetch(`/api/regions/${regionId}/${this.citiesEndpoint}`);
            const data = await response.json();
            this.cities = data.cities || [];
        } finally {
            this.loadingCities = false;
        }
    },
}));

Alpine.data('mileageMax', (min = 0, max = 300000, initialMax = null, anyLabel = 'Any', kmSuffix = 'km') => ({
    min,
    max,
    anyLabel,
    kmSuffix,
    value: initialMax ?? max,

    get fillStyle() {
        const range = this.max - this.min;
        const right = ((this.max - this.value) / range) * 100;

        return {
            left: '0%',
            right: `${right}%`,
        };
    },

    get hiddenTo() {
        return this.value >= this.max ? '' : this.value;
    },

    get label() {
        if (this.value >= this.max) {
            return this.anyLabel;
        }

        return `${new Intl.NumberFormat().format(this.value)} ${this.kmSuffix}`;
    },

    onInput() {
        if (this.value < this.min) {
            this.value = this.min;
        }

        if (this.value > this.max) {
            this.value = this.max;
        }
    },
}));

Alpine.data('regionCityPicker', (initialRegionId = null, initialCity = '') => ({
    regionId: initialRegionId ? String(initialRegionId) : '',
    city: initialCity || '',
    cities: [],
    cityQuery: '',
    cityOpen: false,
    loadingCities: false,

    async init() {
        if (this.regionId) {
            await this.loadCities(this.regionId);
        }

        if (this.city) {
            this.cityQuery = this.city;
        }
    },

    get filteredCities() {
        const query = this.cityQuery.trim().toLowerCase();

        if (!query) {
            return this.cities;
        }

        return this.cities.filter((name) => name.toLowerCase().includes(query));
    },

    async onRegionChange() {
        this.city = '';
        this.cityQuery = '';
        await this.loadCities(this.regionId);
    },

    async loadCities(regionId) {
        if (!regionId) {
            this.cities = [];
            return;
        }

        this.loadingCities = true;

        try {
            const response = await fetch(`/api/regions/${regionId}/cities`);
            const data = await response.json();
            this.cities = data.cities || [];
        } finally {
            this.loadingCities = false;
        }
    },

    selectCity(name) {
        this.city = name;
        this.cityQuery = name;
        this.cityOpen = false;
    },
}));

Alpine.data('favoriteButton', (favoriteUrl, initialFavorited, isAuthenticated, loginUrl) => ({
    favorited: initialFavorited,
    loading: false,

    async toggle() {
        if (!isAuthenticated) {
            window.location.href = loginUrl;
            return;
        }

        this.loading = true;

        try {
            const response = await fetch(favoriteUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    Accept: 'application/json',
                },
            });

            if (response.redirected) {
                window.location.href = response.url;
                return;
            }

            if (!response.ok) {
                return;
            }

            const data = await response.json();
            this.favorited = data.favorited;
        } finally {
            this.loading = false;
        }
    },
}));

Alpine.data('listingGallery', (images = []) => ({
    active: 0,
    images,
    lightboxOpen: false,

    get count() {
        return this.images.length;
    },

    select(index) {
        if (index >= 0 && index < this.count) {
            this.active = index;
        }
    },

    prev() {
        if (!this.count) return;
        this.active = (this.active - 1 + this.count) % this.count;
    },

    next() {
        if (!this.count) return;
        this.active = (this.active + 1) % this.count;
    },

    openLightbox(index = null) {
        if (!this.count) return;
        if (index !== null) {
            this.select(index);
        }
        this.lightboxOpen = true;
        document.body.classList.add('overflow-hidden');
    },

    closeLightbox() {
        this.lightboxOpen = false;
        document.body.classList.remove('overflow-hidden');
    },

    touchStartX: null,

    onTouchStart(event) {
        this.touchStartX = event.changedTouches[0].screenX;
    },

    onTouchEnd(event) {
        if (this.touchStartX === null) return;
        const delta = event.changedTouches[0].screenX - this.touchStartX;
        if (Math.abs(delta) > 40) {
            if (delta < 0) this.next();
            else this.prev();
        }
        this.touchStartX = null;
    },
}));

Alpine.data('listingActions', (shareUrl, shareText) => ({
    shareOpen: false,
    reportOpen: false,

    get facebookUrl() {
        return `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareUrl)}`;
    },

    get whatsappUrl() {
        return `https://wa.me/?text=${encodeURIComponent(shareText + ' ' + shareUrl)}`;
    },

    get viberUrl() {
        return `viber://forward?text=${encodeURIComponent(shareText + ' ' + shareUrl)}`;
    },

    async copyLink() {
        try {
            await navigator.clipboard.writeText(shareUrl);
        } catch {
            // ignore
        }
        this.shareOpen = false;
    },
}));

Alpine.data('tenderBidding', (config) => ({
    stateUrl: config.stateUrl,
    bidUrl: config.bidUrl,
    acceptRulesUrl: config.acceptRulesUrl,
    revokeUrlBase: config.revokeUrlBase || config.stateUrl.replace(/\/state$/, '/bids'),
    loginUrl: config.loginUrl,
    isAuthenticated: config.isAuthenticated,
    isSeller: config.isSeller,
    labels: config.labels,
    eurToBgn: config.eurToBgn ?? 1.95583,
    rulesItems: config.rulesItems || [],
    avatarColors: config.avatarColors || [],
    state: config.initialState,
    bidAmount: config.initialState.minimum_next_bid,
    rulesAccepted: Boolean(config.initialState.rules_accepted),
    rulesModalOpen: false,
    rulesModalMode: 'read',
    rulesLoading: false,
    pendingBidAfterRules: false,
    loading: false,
    error: '',
    pollTimer: null,
    countdownTimer: null,
    localSecondsRemaining: config.initialState.seconds_remaining,

    get canPlaceBid() {
        return this.isAuthenticated
            && !this.isSeller
            && !this.state.my_bid
            && this.state.is_biddable;
    },

    get displayAmount() {
        return this.state.current_high_bid ?? this.state.starting_price;
    },

    get countdownLabel() {
        if (this.state.status === 'awarded') {
            return this.labels.awarded;
        }

        if (!this.state.is_biddable) {
            return this.labels.ended;
        }

        return this.labels.endsIn;
    },

    get countdownDisplay() {
        if (!this.state.is_biddable) {
            return '—';
        }

        const total = Math.max(0, this.localSecondsRemaining);
        const days = Math.floor(total / 86400);
        const hours = Math.floor((total % 86400) / 3600);
        const minutes = Math.floor((total % 3600) / 60);
        const seconds = total % 60;

        const time = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;

        if (days > 0) {
            const dayLabel = days === 1
                ? this.labels.countdownOneDay
                : (this.labels.countdownManyDays || ':count days').replace(':count', String(days));

            return `${dayLabel} ${time}`;
        }

        return time;
    },

    get endedLabel() {
        if (this.state.status === 'awarded') {
            return this.labels.awarded;
        }

        return this.labels.ended;
    },

    get bidCountLabel() {
        const count = this.state.bid_count ?? 0;

        return count === 1 ? '1 bid' : `${count} bids`;
    },

    init() {
        this.snapBidAmount();
        this.startCountdown();
        this.schedulePoll();
    },

    destroy() {
        if (this.pollTimer) {
            clearTimeout(this.pollTimer);
        }

        if (this.countdownTimer) {
            clearInterval(this.countdownTimer);
        }
    },

    startCountdown() {
        this.countdownTimer = setInterval(() => {
            if (this.localSecondsRemaining > 0) {
                this.localSecondsRemaining -= 1;
            }
        }, 1000);
    },

    schedulePoll() {
        const interval = this.state.poll_interval_ms || 15000;

        this.pollTimer = setTimeout(async () => {
            await this.poll();
            this.schedulePoll();
        }, interval);
    },

    async poll() {
        try {
            const response = await fetch(this.stateUrl, {
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) {
                return;
            }

            const data = await response.json();
            this.applyState(data);
        } catch {
            // ignore polling errors
        }
    },

    applyState(data) {
        this.state = {
            ...data,
            bid_ranking: Array.isArray(data.bid_ranking) ? [...data.bid_ranking] : [],
            bid_history: Array.isArray(data.bid_history) ? [...data.bid_history] : [],
        };
        this.localSecondsRemaining = data.seconds_remaining;
        this.bidAmount = data.minimum_next_bid;
        this.snapBidAmount();

        if (typeof data.rules_accepted === 'boolean') {
            this.rulesAccepted = data.rules_accepted;
        }

        if (!data.my_bid) {
            this.error = '';
        }
    },

    snapBidAmount() {
        const min = Number(this.state.minimum_next_bid ?? 0);
        const step = Number(this.state.bid_increment ?? 100);
        const start = Number(this.state.starting_price ?? 0);

        if (!min || !step) {
            return;
        }

        if (this.bidAmount < min) {
            this.bidAmount = min;
        }

        const remainder = (this.bidAmount - start) % step;

        if (remainder !== 0) {
            this.bidAmount = this.bidAmount - remainder + (remainder > step / 2 ? step : 0);

            if (this.bidAmount < min) {
                this.bidAmount = min;
            }
        }
    },

    increaseBid() {
        const step = Number(this.state.bid_increment ?? 100);
        const min = Number(this.state.minimum_next_bid ?? 0);
        this.bidAmount = Math.max(min, Number(this.bidAmount || min) + step);
        this.snapBidAmount();
    },

    decreaseBid() {
        const step = Number(this.state.bid_increment ?? 100);
        const min = Number(this.state.minimum_next_bid ?? 0);
        this.bidAmount = Math.max(min, Number(this.bidAmount || min) - step);
        this.snapBidAmount();
    },

    isValidBidAmount(amount) {
        const min = Number(this.state.minimum_next_bid ?? 0);
        const step = Number(this.state.bid_increment ?? 100);
        const start = Number(this.state.starting_price ?? 0);

        return amount >= min && (amount - start) % step === 0;
    },

    bidStatusLabel(entry) {
        if (entry?.is_leader) {
            return entry.status === 'won' ? this.labels.statusWon : this.labels.statusActive;
        }

        if (entry?.status === 'won') {
            return this.labels.statusWon;
        }

        return this.labels.statusOutbid;
    },

    openRulesModal(mode) {
        this.rulesModalMode = mode;
        this.rulesModalOpen = true;
        document.body.classList.add('overflow-hidden');
    },

    closeRulesModal() {
        this.rulesModalOpen = false;
        this.pendingBidAfterRules = false;
        document.body.classList.remove('overflow-hidden');
    },

    async acceptRules() {
        this.rulesLoading = true;

        try {
            const response = await fetch(this.acceptRulesUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    Accept: 'application/json',
                },
            });

            if (!response.ok) {
                return;
            }

            this.rulesAccepted = true;

            const shouldPlaceBid = this.pendingBidAfterRules;
            this.pendingBidAfterRules = false;
            this.rulesModalOpen = false;
            document.body.classList.remove('overflow-hidden');

            if (shouldPlaceBid) {
                await this.placeBid();
            }
        } finally {
            this.rulesLoading = false;
        }
    },

    formatMoney(value) {
        if (value === null || value === undefined) {
            return '—';
        }

        return `${new Intl.NumberFormat().format(value)} €`;
    },

    formatMoneyBgn(value) {
        if (value === null || value === undefined) {
            return '—';
        }

        const bgn = Math.round(Number(value) * this.eurToBgn);

        return `${new Intl.NumberFormat().format(bgn)} ${this.labels.bgn || 'BGN'}`;
    },

    async placeBid() {
        if (!this.rulesAccepted) {
            this.pendingBidAfterRules = true;
            this.openRulesModal('agree');
            return;
        }

        this.snapBidAmount();

        if (!this.isValidBidAmount(Number(this.bidAmount))) {
            this.error = (this.labels.bidTooLow || 'Minimum bid is :min €').replace(
                ':min',
                new Intl.NumberFormat().format(this.state.minimum_next_bid),
            );
            return;
        }

        this.pendingBidAfterRules = false;
        this.loading = true;
        this.error = '';

        try {
            const response = await fetch(this.bidUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    Accept: 'application/json',
                },
                body: JSON.stringify({ amount: this.bidAmount }),
            });

            const data = await response.json();

            if (!response.ok) {
                this.error = data.message || Object.values(data.errors || {}).flat().join(' ');
                return;
            }

            this.applyState(data.state);
        } catch {
            this.error = 'Request failed';
        } finally {
            this.loading = false;
        }
    },

    async revokeBid() {
        if (!this.state.my_bid?.id) {
            return;
        }

        this.loading = true;
        this.error = '';

        try {
            const response = await fetch(`${this.revokeUrlBase}/${this.state.my_bid.id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    Accept: 'application/json',
                },
            });

            const data = await response.json();

            if (!response.ok) {
                this.error = data.message || Object.values(data.errors || {}).flat().join(' ');
                return;
            }

            this.applyState(data.state);
        } catch {
            this.error = 'Request failed';
        } finally {
            this.loading = false;
        }
    },
}));

Alpine.data('mobileBgImport', (runId = null, statusUrl = '') => ({
    runId,
    statusUrl,
    status: null,
    polling: false,

    init() {
        if (this.runId && this.statusUrl) {
            this.poll();
        }
    },

    async poll() {
        this.polling = true;

        const tick = async () => {
            try {
                const response = await fetch(this.statusUrl, {
                    headers: { Accept: 'application/json' },
                });

                if (response.ok) {
                    this.status = await response.json();

                    if (!this.status.finished) {
                        setTimeout(tick, 2000);
                        return;
                    }
                }
            } catch {
                // ignore polling errors
            }

            this.polling = false;
        };

        await tick();
    },
}));

Alpine.data('leasingCalculator', (price, banks, config) => ({
    banks,
    bankSlug: banks[0]?.slug ?? '',
    downPaymentPercent: config.default_down_payment_percent ?? 20,
    months: 60,
    eurToBgn: config.eur_to_bgn ?? 1.95583,

    get selectedBank() {
        return this.banks.find((b) => b.slug === this.bankSlug) ?? this.banks[0];
    },

    get maxMonths() {
        return Math.min(config.max_months ?? 96, this.selectedBank?.max_months ?? 96);
    },

    get downPaymentAmount() {
        return Math.round(price * (this.downPaymentPercent / 100));
    },

    get principal() {
        return Math.max(price - this.downPaymentAmount, 0);
    },

    get monthlyRate() {
        return (this.selectedBank?.annual_rate ?? 8) / 100 / 12;
    },

    get monthlyPayment() {
        const p = this.principal;
        const r = this.monthlyRate;
        const n = this.months;
        if (!p || !r || !n) return 0;
        const factor = Math.pow(1 + r, n);
        return Math.round((p * r * factor) / (factor - 1));
    },

    get monthlyPaymentBgn() {
        return Math.round(this.monthlyPayment * this.eurToBgn);
    },

    get totalPaid() {
        return this.downPaymentAmount + this.monthlyPayment * this.months;
    },

    get totalInterest() {
        return Math.max(this.totalPaid - price, 0);
    },

    syncBank() {
        const bank = this.selectedBank;
        if (bank && this.downPaymentPercent < bank.min_down_payment) {
            this.downPaymentPercent = bank.min_down_payment;
        }
        if (this.months > this.maxMonths) {
            this.months = this.maxMonths;
        }
    },

    formatMoney(value) {
        return new Intl.NumberFormat().format(Math.round(value));
    },
}));

Alpine.data('compareButton', (listingSlug, addUrl) => ({
    loading: false,

    async add() {
        this.loading = true;

        try {
            const response = await fetch(addUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    Accept: 'application/json',
                },
            });

            if (!response.ok) {
                return;
            }

            const data = await response.json();
            window.dispatchEvent(new CustomEvent('compare-updated', { detail: data }));
        } finally {
            this.loading = false;
        }
    },
}));

Alpine.data('compareTray', (compareUrl, stateUrl) => ({
    count: 0,
    compareUrl,
    stateUrl,

    get label() {
        return this.count === 1
            ? document.documentElement.lang.startsWith('bg')
                ? '1 обява за сравнение'
                : '1 car to compare'
            : document.documentElement.lang.startsWith('bg')
              ? `${this.count} обяви за сравнение`
              : `${this.count} cars to compare`;
    },

    async init() {
        await this.refresh();
        window.addEventListener('compare-updated', (event) => {
            this.count = event.detail?.count ?? this.count;
        });
    },

    async refresh() {
        try {
            const response = await fetch(this.stateUrl, { headers: { Accept: 'application/json' } });
            if (response.ok) {
                const data = await response.json();
                this.count = data.count ?? 0;
            }
        } catch {
            // ignore
        }
    },

    async clear() {
        await fetch('/compare/clear', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                Accept: 'application/json',
            },
        });
        this.count = 0;
    },
}));

function trackPhoneClick(url) {
    if (!url) {
        return;
    }

    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    if (!token) {
        return;
    }

    fetch(url, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': token,
        },
        keepalive: true,
    }).catch(() => {});
}

window.trackPhoneClick = trackPhoneClick;

Alpine.data('phoneReveal', (config) => ({
    tel: config.tel,
    display: config.display,
    masked: config.masked,
    trackUrl: config.trackUrl,
    revealHint: config.revealHint,
    callHint: config.callHint,
    revealed: false,

    get label() {
        return this.revealed ? this.display : this.masked;
    },

    handleClick() {
        if (!this.revealed) {
            this.revealed = true;
            return;
        }

        trackPhoneClick(this.trackUrl);
        window.location.href = `tel:${this.tel}`;
    },
}));



Alpine.data('subscriptionToggles', (initialSettings, updateUrl) => ({
    settings: { ...initialSettings },
    saving: false,

    async toggle(key, enabled) {
        const previous = this.settings[key];
        this.settings[key] = enabled;
        this.saving = true;

        try {
            const response = await fetch(updateUrl, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    Accept: 'application/json',
                },
                body: JSON.stringify({ key, enabled }),
            });

            if (!response.ok) {
                this.settings[key] = previous;
                return;
            }

            const data = await response.json();
            this.settings[key] = data.enabled;
        } catch {
            this.settings[key] = previous;
        } finally {
            this.saving = false;
        }
    },
}));

Alpine.data('subscriptionPrompt', (initialSettings, updateUrl, dismissUrl, manageUrl) => ({
    settings: { ...initialSettings },
    visible: false,
    savingKey: null,
    manageUrl,

    init() {
        this.visible = true;
    },

    isSaving(key) {
        return this.savingKey === key;
    },

    async toggle(key, enabled) {
        const previous = this.settings[key];
        this.settings[key] = enabled;
        this.savingKey = key;

        try {
            const response = await fetch(updateUrl, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    Accept: 'application/json',
                },
                body: JSON.stringify({ key, enabled }),
            });

            if (!response.ok) {
                this.settings[key] = previous;
                return;
            }

            const data = await response.json();
            this.settings[key] = data.enabled;
        } catch {
            this.settings[key] = previous;
        } finally {
            this.savingKey = null;
        }
    },

    async dismiss(markPrompted = true) {
        this.visible = false;

        if (!markPrompted) {
            return;
        }

        try {
            await fetch(dismissUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    Accept: 'application/json',
                },
            });
        } catch {
            // ignore
        }
    },
}));

function initDealersMap() {
    const container = document.getElementById('dealers-map');
    if (!container || typeof window.L === 'undefined') {
        return;
    }

    const center = JSON.parse(container.dataset.center || '{}');
    const markers = JSON.parse(container.dataset.markers || '[]');
    const listingsLabel = container.dataset.listingsLabel || 'listings';

    const map = window.L.map(container).setView([center.lat, center.lng], center.zoom || 8);

    window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap',
        maxZoom: 18,
    }).addTo(map);

    const group = window.L.featureGroup();

    markers.forEach((marker) => {
        const listingsText = `${new Intl.NumberFormat().format(marker.listings)} ${listingsLabel}`;
        const verifiedBadge = marker.verified ? ' ✓' : '';
        const cityLine = marker.city ? `<br><span class="text-sm">${marker.city}</span>` : '';

        const popup = window.L.popup().setContent(
            `<a href="${marker.url}" class="font-medium">${marker.name}${verifiedBadge}</a>${cityLine}<br><span class="text-sm">${listingsText}</span>`,
        );

        window.L.marker([marker.lat, marker.lng]).bindPopup(popup).addTo(group);
    });

    group.addTo(map);

    if (markers.length > 0) {
        map.fitBounds(group.getBounds().pad(0.12));
    }
}

document.addEventListener('DOMContentLoaded', initDealersMap);

Alpine.start();