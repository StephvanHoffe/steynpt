// Bewerkscherm voor Steyn (PlanEditor in de Next.js-versie). De inhoud gaat als JSON in het verborgen veld
// "content" naar de server en wordt daar opnieuw gecontroleerd met hetzelfde schema als de AI-output
// (App\Support\Plans\PlanSchema).
import Alpine from 'alpinejs';
import { addDays, daysBetween, defaultRenewOn, findAllergenWarnings, formatDay, relativeDay } from './plan-utils.js';

const clone = (value) => JSON.parse(JSON.stringify(value));
const cleanLines = (lines) => lines.map((l) => l.trim()).filter(Boolean);

function normalize(plan) {
    return 'days' in plan
        ? { ...plan, tips: cleanLines(plan.tips), daysPerWeek: plan.days.length }
        : { ...plan, tips: cleanLines(plan.tips), avoid: cleanLines(plan.avoid) };
}

Alpine.data('planEditor', (config) => {
    const type = 'days' in config.initial ? 'training' : 'voeding';
    const live = config.status === 'gepubliceerd';
    const renewFor = (start, plan) => defaultRenewOn(config.renewWeeks, type, start, type === 'training' ? plan.durationWeeks : null);

    // Na een fout op de server: de ingevulde inhoud en datums terugzetten (ze zijn nog niet opgeslagen).
    const old = config.old;
    const plan = clone(old?.content ?? config.initial);
    const start = old?.startsOn || config.startsOn;
    let picked = config.followDuration ? null : config.renewOn;
    if (old?.renewOn) picked = config.followDuration && old.renewOn === renewFor(start, plan) ? null : old.renewOn;
    const savedKey = `${JSON.stringify(normalize(config.initial))}|${config.followDuration ? renewFor(config.startsOn, config.initial) : config.renewOn}|${config.startsOn}`;

    return {
        plan,
        tab: 'bewerken',
        start,
        pickedRenewOn: picked,
        saved: savedKey,
        today: config.today,
        live,
        isTraining: type === 'training',
        preview: { html: '', loading: false, error: '' },

        get json() {
            return JSON.stringify(normalize(this.plan));
        },
        get renewOn() {
            return this.pickedRenewOn ?? renewFor(this.start, this.plan);
        },
        get later() {
            return !live && this.start > config.today;
        },
        get dirty() {
            return `${this.json}|${this.renewOn}|${this.start}` !== this.saved;
        },
        get warnings() {
            if (this.isTraining || !config.allergenCheck) return [];
            // Zolang er niets gewijzigd is, gelden de waarschuwingen die de server heeft berekend.
            if (this.json === JSON.stringify(normalize(config.initial))) return config.warnings;
            return findAllergenWarnings(this.plan, config.allergenCheck);
        },
        get minRenew() {
            return addDays(this.later ? this.start : config.today, 1);
        },
        // Een gepubliceerd schema met een al verstreken datum mag zonder wijziging opgeslagen worden.
        get minRenewAttr() {
            return live && this.renewOn === config.renewOn ? null : this.minRenew;
        },
        get maxRenew() {
            return addDays(this.later ? this.start : config.today, 366);
        },
        get weeksAfterStart() {
            return Math.round(daysBetween(this.start, this.renewOn) / 7);
        },
        get macroKcal() {
            const t = this.plan.targets;
            return Math.round(t.protein * 4 + t.carbs * 4 + t.fat * 9);
        },
        get publishLabel() {
            if (live) return 'Opnieuw publiceren';
            if (this.later) return config.status === 'gepland' ? 'Opnieuw inplannen' : 'Goedkeuren & inplannen';
            return config.status === 'gepland' ? 'Nu publiceren' : 'Goedkeuren & publiceren';
        },
        get startHint() {
            return this.later ? `zichtbaar voor de klant vanaf ${formatDay(this.start)} (${relativeDay(config.today, this.start)})` : 'direct na publiceren';
        },
        get renewHint() {
            const n = this.weeksAfterStart;
            const base =
                this.renewOn <= config.today
                    ? 'verlopen, kies een nieuwe datum'
                    : this.later
                      ? `${n} ${n === 1 ? 'week' : 'weken'} na de start`
                      : relativeDay(config.today, this.renewOn);
            return base + (config.followDuration && this.pickedRenewOn === null && this.isTraining ? ' · volgt de duur van het schema' : '');
        },

        // Een andere startdatum verschuift een zelfgekozen "nieuw schema"-datum mee, zodat de looptijd gelijk blijft.
        changeStart(day) {
            if (!day) return;
            if (this.pickedRenewOn) this.pickedRenewOn = addDays(this.pickedRenewOn, daysBetween(this.start, day));
            this.start = day;
        },
        pickRenewOn(day) {
            this.pickedRenewOn = day || null;
        },

        // Getalvelden: leeg telt als 0, en een half getypt getal ("1.") blijft staan.
        numValue(el, value) {
            const current = el.value;
            if (current !== '' && Number(current) === value) return current;
            return String(Number.isFinite(value) ? value : 0);
        },
        toNumber(value) {
            return value === '' ? 0 : Number(value);
        },
        lines(list) {
            return list.join('\n');
        },
        splitLines(value) {
            return value.split('\n');
        },

        addDay() {
            this.plan.days.push({ ...clone(config.empty.day), name: `Dag ${this.plan.days.length + 1}` });
        },
        addExercise(day) {
            day.exercises.push(clone(config.empty.exercise));
        },
        addMeal() {
            this.plan.meals.push(clone(config.empty.meal));
        },
        addOption(meal) {
            meal.options.push(clone(config.empty.option));
        },
        removeAt(list, index) {
            list.splice(index, 1);
        },

        async showPreview() {
            this.tab = 'voorbeeld';
            this.preview.loading = true;
            this.preview.error = '';
            try {
                const response = await fetch(config.previewUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'text/html',
                        'X-CSRF-TOKEN': this.$root.querySelector('input[name=_token]').value,
                    },
                    body: JSON.stringify({ content: this.plan }),
                });
                if (!response.ok) throw new Error(await response.text());
                this.preview.html = await response.text();
            } catch (error) {
                this.preview.html = '';
                this.preview.error = 'Het voorbeeld kon niet getoond worden. Controleer of alle getallen zijn ingevuld.';
            } finally {
                this.preview.loading = false;
            }
        },

        onSubmit(event) {
            const intent = event.submitter?.value;
            if (intent === 'publiceren') {
                const n = this.warnings.length;
                const extra = n ? `\n\nLet op: er ${n === 1 ? 'is 1 waarschuwing' : `zijn ${n} waarschuwingen`} over allergieën/eetstijl.` : '';
                const when = this.later
                    ? `Schema inplannen? De klant ziet het vanaf ${formatDay(this.start)} in Mijn omgeving.`
                    : 'Schema publiceren? De klant ziet het daarna direct in Mijn omgeving.';
                if (!window.confirm(`${when} Volgend schema: ${formatDay(this.renewOn)}.${extra}`)) {
                    event.preventDefault();
                }
            }
        },
    };
});
