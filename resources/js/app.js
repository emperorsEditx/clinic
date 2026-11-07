import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';

window.Alpine = Alpine;
Alpine.plugin(focus);

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content;

async function getJson(url) {
    const res = await fetch(url, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
    if (!res.ok) throw new Error(`Request failed (${res.status})`);
    return res.json();
}

// Preserves `this` so debounced methods on Alpine components keep access to component state.
const debounce = (fn, ms = 250) => {
    let t;
    return function (...args) { clearTimeout(t); t = setTimeout(() => fn.apply(this, args), ms); };
};

/* ---------- Toasts ---------- */
Alpine.store('toasts', {
    items: [],
    push(message, type = 'success') {
        const id = Date.now() + Math.random();
        this.items.push({ id, message, type });
        setTimeout(() => this.remove(id), type === 'error' ? 8000 : 4500);
    },
    remove(id) { this.items = this.items.filter((t) => t.id !== id); },
});

/* ---------- Confirmation dialog for destructive actions ----------
 * <form data-confirm="Message" data-confirm-title="Title" data-confirm-reason="reason" data-confirm-danger>
 * When data-confirm-reason is set, the dialog asks for a reason and submits it in that field.
 */
Alpine.store('confirm', {
    open: false, title: '', message: '', reasonField: null, reason: '', danger: false, form: null, confirmLabel: 'Confirm',
    ask(form) {
        this.form = form;
        this.title = form.dataset.confirmTitle || 'Are you sure?';
        this.message = form.dataset.confirm || '';
        this.reasonField = form.dataset.confirmReason || null;
        this.confirmLabel = form.dataset.confirmLabel || 'Confirm';
        this.danger = form.hasAttribute('data-confirm-danger');
        this.reason = '';
        this.open = true;
    },
    accept() {
        if (this.reasonField && !this.reason.trim()) return;
        const form = this.form;
        if (this.reasonField) {
            let input = form.querySelector(`input[name="${this.reasonField}"]`);
            if (!input) {
                input = document.createElement('input');
                input.type = 'hidden';
                input.name = this.reasonField;
                form.appendChild(input);
            }
            input.value = this.reason.trim();
        }
        form.dataset.confirmed = '1';
        this.open = false;
        form.requestSubmit ? form.requestSubmit() : form.submit();
    },
});

document.addEventListener('submit', (e) => {
    const form = e.target;
    if (form.matches('form[data-confirm]') && form.dataset.confirmed !== '1') {
        e.preventDefault();
        Alpine.store('confirm').ask(form);
        return;
    }
    // Loading state + double-submit protection.
    if (!e.defaultPrevented) {
        const btn = e.submitter;
        if (form.dataset.submitting === '1') { e.preventDefault(); return; }
        form.dataset.submitting = '1';
        if (btn && !form.hasAttribute('data-no-loading')) {
            btn.dataset.originalText = btn.innerHTML;
            btn.classList.add('opacity-70', 'pointer-events-none');
            btn.setAttribute('aria-busy', 'true');
        }
        // Re-enable if the page is restored from bfcache or for downloads.
        setTimeout(() => { delete form.dataset.submitting; btn?.classList.remove('opacity-70', 'pointer-events-none'); btn?.removeAttribute('aria-busy'); }, form.hasAttribute('data-download') ? 1500 : 15000);
    }
});

/* ---------- Searchable patient picker / global search ---------- */
Alpine.data('patientSearch', ({ url, name = null, selected = null, navigate = false } = {}) => ({
    query: '', results: [], open: false, loading: false, active: -1, selected, error: null,
    init() {
        this.search = debounce(this.search.bind(this), 220);
    },
    async search() {
        if (this.query.trim().length < 2) { this.results = []; this.open = false; return; }
        this.loading = true; this.error = null;
        try {
            this.results = await getJson(`${url}?q=${encodeURIComponent(this.query.trim())}`);
            this.open = true; this.active = this.results.length ? 0 : -1;
        } catch (e) { this.error = 'Search failed. Try again.'; this.open = true; }
        finally { this.loading = false; }
    },
    move(d) { if (!this.results.length) return; this.active = (this.active + d + this.results.length) % this.results.length; },
    choose(item) {
        if (!item) return;
        if (navigate) { window.location = item.url; return; }
        this.selected = item; this.open = false; this.query = '';
        this.$dispatch('patient-selected', item);
    },
    clear() { this.selected = null; this.$nextTick(() => this.$refs.input?.focus()); },
}));

/* ---------- Prescription builder ---------- */
const blankItem = () => ({ medicine_id: '', medicine_name: '', generic_name: '', strength: '', dosage: '', route: '', frequency: '', timing: '', duration_days: '', quantity: '', instructions: '' });

Alpine.data('prescriptionBuilder', ({ items = [], searchUrl, templates = [] } = {}) => ({
    items: items.length ? items.map((i) => ({ ...blankItem(), ...i, _key: Math.random() })) : [],
    templates,
    suggestions: {}, openFor: null,
    add(data = {}) {
        this.items.push({ ...blankItem(), ...data, _key: Math.random() });
        this.$nextTick(() => {
            const rows = this.$root.querySelectorAll('[data-medicine-input]');
            rows[rows.length - 1]?.focus();
        });
    },
    remove(index) { this.items.splice(index, 1); },
    move(index, d) {
        const j = index + d;
        if (j < 0 || j >= this.items.length) return;
        [this.items[index], this.items[j]] = [this.items[j], this.items[index]];
    },
    applyTemplate(id) {
        const t = this.templates.find((x) => String(x.id) === String(id));
        if (!t) return;
        t.items.forEach((i) => this.add(i));
        if (t.general_instructions) this.$dispatch('template-instructions', t.general_instructions);
    },
    lookup: debounce(async function (index, term) {
        if (!term || term.trim().length < 2) { this.suggestions[index] = []; return; }
        try {
            this.suggestions[index] = await getJson(`${searchUrl}?q=${encodeURIComponent(term.trim())}`);
            this.openFor = index;
        } catch (e) { this.suggestions[index] = []; }
    }, 220),
    pick(index, med) {
        const item = this.items[index];
        item.medicine_id = med.id;
        item.medicine_name = med.name;
        item.generic_name = med.generic_name || '';
        item.strength = med.strength || '';
        if (!item.route && med.route) item.route = med.route;
        this.openFor = null;
    },
    unlink(index) { this.items[index].medicine_id = ''; },
    totalDays() { return this.items.reduce((m, i) => Math.max(m, parseInt(i.duration_days || 0, 10)), 0); },
}));

/* ---------- Repeatable rows (lab tests in consultation) ---------- */
Alpine.data('rows', (initial = []) => ({
    rows: initial,
    add(row = {}) { this.rows.push({ _key: Math.random(), ...row }); },
    remove(i) { this.rows.splice(i, 1); },
}));

Alpine.start();
