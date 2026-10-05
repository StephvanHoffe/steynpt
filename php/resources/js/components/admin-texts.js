// Bewerkscherm voor de teksten van één pagina (Website-teksten in het beheer).
// Alle teksten gaan als JSON (verborgen veld "values") naar de server en worden daar opnieuw gecontroleerd
// (App\Content\Values::normalizePage). Na opslaan laadt de pagina opnieuw (PRG); bij fouten komen de ingestuurde
// teksten en de foutmeldingen mee terug, zodat het scherm er hetzelfde uitziet als vóór het opslaan.
import Alpine from 'alpinejs';

const LEAVE_WARNING = 'Je hebt wijzigingen die nog niet zijn opgeslagen. Weet je zeker dat je deze pagina wilt verlaten?';
const SCROLL_KEY = 'beheer-teksten-scroll';

// --- Zelfde hulpjes als src/lib/content/{markup,values,vars}.ts ---

const PLACEHOLDER = /\{([a-z][a-z0-9-]*)\}/g;
const placeholdersIn = (value) => [...new Set([...value.matchAll(PLACEHOLDER)].map((m) => m[1]))];
const fillText = (value, vars) => value.replace(PLACEHOLDER, (whole, key) => (key in vars ? vars[key] : whole));

const stable = (value) =>
    Array.isArray(value)
        ? value.map(stable)
        : value && typeof value === 'object'
          ? Object.fromEntries(Object.keys(value).sort().map((k) => [k, stable(value[k])]))
          : value;
const sameValue = (a, b) => JSON.stringify(stable(a)) === JSON.stringify(stable(b));

const priceNumber = (value) => Number(String(value).replace(/\./g, '').replace(',', '.'));
const formatPrice = (n) => (Number.isInteger(n) ? String(n) : n.toFixed(2).replace('.', ','));

function computeVars(shared, prices) {
    const online = (prices.online?.plans ?? []).map((p) => priceNumber(p.price)).filter((n) => Number.isFinite(n));
    return {
        actie: shared.vriendenactie.headline,
        vriendkorting: shared.vriendenactie.friendReward,
        jouwkorting: shared.vriendenactie.referrerReward,
        ademprijs: prices.adem.price,
        ademduur: prices.adem.duration,
        'online-vanaf': online.length ? formatPrice(Math.min(...online)) : '',
    };
}

function parseRich(value) {
    const parts = [];
    value.split('\n').forEach((line, i) => {
        if (i > 0) parts.push({ br: true });
        const pieces = line.split('*');
        // Bij een oneven aantal sterretjes is het laatste niet gesloten: dat is gewoon een teken.
        if (pieces.length % 2 === 0) pieces.splice(pieces.length - 2, 2, `${pieces[pieces.length - 2]}*${pieces[pieces.length - 1]}`);
        pieces.forEach((text, j) => text && parts.push({ text, accent: j % 2 === 1 }));
    });
    return parts;
}

const escapeHtml = (text) => text.replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
const richHtml = (text) =>
    parseRich(text)
        .map((p) => ('br' in p ? '<br>' : p.accent ? `<span class="text-accent">${escapeHtml(p.text)}</span>` : escapeHtml(p.text)))
        .join('');

// Kopie zonder Alpine-proxy's (structuredClone werkt niet op een proxy).
const clone = (value) => (value === undefined ? undefined : JSON.parse(JSON.stringify(value)));
const getAt = (values, path) => path.split('.').reduce((v, k) => (v && typeof v === 'object' ? v[k] : undefined), values);
const count = (n, one, many) => `${n} ${n === 1 ? one : many}`;

const storage = {
    get(key) {
        try {
            return window.sessionStorage.getItem(key);
        } catch {
            return null;
        }
    },
    set(key, value) {
        try {
            if (value === null) window.sessionStorage.removeItem(key);
            else window.sessionStorage.setItem(key, value);
        } catch {
            // Geen opslag beschikbaar: dan alleen geen scrollpositie onthouden.
        }
    },
};

Alpine.data('adminTexts', (config) => {
    const { page, initial, shared, vars: varInfo, state } = config;
    const sections = Object.keys(page.sections);
    // Na een mislukte poging: de ingestuurde teksten weer tonen (aangevuld met de rest van de pagina).
    const submitted = state.submitted && typeof state.submitted === 'object'
        ? Object.fromEntries(sections.map((s) => [s, { ...initial[s], ...(state.submitted[s] ?? {}) }]))
        : null;

    return {
        values: clone(submitted ?? initial),
        saved: clone(initial),
        submitted,
        fieldErrors: state.fieldErrors ?? {},
        error: state.error ?? null,
        success: state.success ?? null,
        pending: false,
        leaving: false,

        init() {
            // Niet zomaar weg met niet-opgeslagen wijzigingen.
            window.addEventListener('beforeunload', (e) => {
                if (this.isAnyDirty && !this.leaving) {
                    e.preventDefault();
                    e.returnValue = '';
                }
            });
            document.addEventListener(
                'click',
                (e) => {
                    if (!this.isAnyDirty || this.leaving) return;
                    const a = e.target?.closest?.('a[href]');
                    if (!a || a.getAttribute('target') === '_blank' || a.getAttribute('href')?.startsWith('#')) return;
                    if (window.confirm(LEAVE_WARNING)) {
                        this.leaving = true;
                    } else {
                        e.preventDefault();
                        e.stopPropagation();
                    }
                },
                true,
            );

            this.$nextTick(() => {
                const scroll = storage.get(`${SCROLL_KEY}:${page.slug}`);
                storage.set(`${SCROLL_KEY}:${page.slug}`, null);
                if (Object.keys(this.fieldErrors).length) {
                    // Na een mislukte poging: naar het eerste veld met een fout.
                    const el = document.querySelector('[aria-invalid="true"]');
                    el?.scrollIntoView({ block: 'center' });
                    el?.focus({ preventScroll: true });
                } else if (scroll !== null && this.success) {
                    // Na opslaan: terug naar waar je was.
                    window.scrollTo({ top: Number(scroll), behavior: 'instant' });
                }
            });
        },

        // Ctrl/Cmd + S slaat op.
        onKey(e) {
            if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 's') {
                e.preventDefault();
                this.$root.requestSubmit();
            }
        },

        onSubmit() {
            this.$refs.values.value = JSON.stringify(this.values);
            this.pending = true;
            this.leaving = true;
            storage.set(`${SCROLL_KEY}:${page.slug}`, String(window.scrollY));
        },

        get json() {
            return JSON.stringify(this.values);
        },

        // Automatische waarden, met wat je op deze pagina zelf aan het aanpassen bent.
        get vars() {
            return computeVars(page.slug === 'algemeen' ? this.values : shared.algemeen, page.slug === 'pakketten' ? this.values : shared.pakketten);
        },

        get dirty() {
            const out = [];
            for (const s of sections) for (const f of Object.keys(page.sections[s].fields)) if (!sameValue(this.values[s]?.[f], this.saved[s]?.[f])) out.push(`${s}.${f}`);
            return out;
        },

        get isAnyDirty() {
            return this.dirty.length > 0;
        },

        isDirty(path) {
            return this.dirty.includes(path);
        },

        // Een foutmelding verdwijnt zodra je het veld na het opslaan hebt aangepast.
        errorAt(path) {
            return this.fieldErrors[path] && this.submitted && sameValue(getAt(this.values, path), getAt(this.submitted, path)) ? this.fieldErrors[path] : undefined;
        },

        get openErrors() {
            return Object.keys(this.fieldErrors).filter((p) => this.errorAt(p));
        },

        sectionState(s) {
            return {
                dirty: this.dirty.some((d) => d.startsWith(`${s}.`)),
                error: this.openErrors.some((p) => p.startsWith(`${s}.`)),
            };
        },

        field(path) {
            const [s, f, , k] = path.split('.');
            const field = page.sections[s].fields[f];
            return k === undefined ? field : field.fields[k];
        },

        custom(path) {
            return !sameValue(getAt(this.values, path), this.field(path).default);
        },

        resetField(path) {
            const [s, f] = path.split('.');
            this.values[s][f] = clone(this.field(path).default);
        },

        get anyCustom() {
            return sections.some((s) => Object.keys(page.sections[s].fields).some((f) => this.custom(`${s}.${f}`)));
        },

        resetAll() {
            if (!window.confirm('Alle teksten op deze pagina terugzetten naar de standaardtekst? Dit wordt pas definitief als je opslaat.')) return;
            this.values = Object.fromEntries(
                sections.map((s) => [s, Object.fromEntries(Object.entries(page.sections[s].fields).map(([f, field]) => [f, clone(field.default)]))]),
            );
        },

        undo() {
            this.values = clone(this.saved);
        },

        get usedVars() {
            const json = this.json;
            return varInfo.filter((v) => json.includes(`{${v.key}}`) || v.page === page.slug);
        },

        // --- Lijsten met items (vragen, reviews, pakketten) ---

        addItem(s, f) {
            const field = page.sections[s].fields[f];
            this.values[s][f].push(Object.fromEntries(Object.entries(field.fields).map(([k, sub]) => [k, clone(sub.default)])));
        },

        moveItem(s, f, i, d) {
            const next = [...this.values[s][f]];
            [next[i], next[i + d]] = [next[i + d], next[i]];
            this.values[s][f] = next;
        },

        removeItem(s, f, i) {
            this.values[s][f] = this.values[s][f].filter((_, j) => j !== i);
        },

        itemTitle(item, key) {
            const title = typeof item?.[key] === 'string' ? item[key] : '';
            return title ? fillText(title, this.vars) : '';
        },

        // --- Onder een veld: teller, onbekende codes en voorvertoning ---

        texts(path) {
            const value = getAt(this.values, path);
            return Array.isArray(value) ? value.filter((v) => String(v).trim()) : typeof value === 'string' ? [value] : [];
        },

        listCount(path) {
            return count(this.texts(path).length, 'punt', 'punten');
        },

        lengthOf(path) {
            const value = getAt(this.values, path);
            return typeof value === 'string' ? value.trim().length : 0;
        },

        unknownCodes(path) {
            const vars = this.vars;
            return [...new Set(this.texts(path).flatMap(placeholdersIn))].filter((k) => !(k in vars));
        },

        unknownText(path) {
            return `Onbekende code ${this.unknownCodes(path).map((u) => `{${u}}`).join(', ')}. Gebruik alleen de automatische waarden uit het lijstje.`;
        },

        previewHtml(path, rich) {
            if (this.unknownCodes(path).length) return '';
            const vars = this.vars;
            const preview = this.texts(path).filter((t) => placeholdersIn(t).length > 0 || (rich && /[*\n]/.test(t)));
            return preview
                .map((t, i) => `<span class="text-ink">${i > 0 ? ' · ' : ''}${rich ? richHtml(fillText(t, vars)) : escapeHtml(fillText(t, vars))}</span>`)
                .join('');
        },

        // --- Status onderaan ---

        get status() {
            if (this.pending) return 'pending';
            if (this.error && this.openErrors.length > 0) return 'error';
            if (this.dirty.length > 0) return 'dirty';
            if (this.success) return 'success';
            return 'idle';
        },

        get dirtyText() {
            return `${count(this.dirty.length, 'wijziging', 'wijzigingen')} nog niet opgeslagen`;
        },
    };
});
