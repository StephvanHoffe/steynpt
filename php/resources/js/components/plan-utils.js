// Hulpfuncties voor de schema-editor en het formulier voor een nieuw schema (gelijk aan de Next.js-versie).
// Dagen zijn "YYYY-MM-DD" (Nederlandse kalenderdag).

export function addDays(day, n) {
    const [y, m, d] = day.split('-').map(Number);
    return new Date(Date.UTC(y, m - 1, d + n)).toISOString().slice(0, 10);
}

export function daysBetween(from, to) {
    return Math.round((Date.parse(`${to}T00:00:00Z`) - Date.parse(`${from}T00:00:00Z`)) / 864e5);
}

/** "vandaag", "over 5 dagen", "3 weken geleden" … */
export function relativeDay(today, day) {
    const n = daysBetween(today, day);
    if (n === 0) return 'vandaag';
    if (n === 1) return 'morgen';
    if (n === -1) return 'gisteren';
    const abs = Math.abs(n);
    const amount = abs < 14 ? `${abs} dagen` : `${Math.round(abs / 7)} weken`;
    return n > 0 ? `over ${amount}` : `${amount} geleden`;
}

/**
 * Looptijd van een schema: een trainingsschema volgens de duur in het schema, anders de standaard per type.
 * renewWeeks komt van de server (App\Support\Plans\Pipeline::defaultRenewWeeks).
 */
export function defaultRenewOn(renewWeeks, type, fromDay, durationWeeks) {
    const weeks = type === 'training' && durationWeeks && durationWeeks >= 1 && durationWeeks <= 26 ? Math.round(durationWeeks) : renewWeeks[type];
    return addDays(fromDay, weeks * 7);
}

const dayLongFmt = new Intl.DateTimeFormat('nl-NL', { weekday: 'long', day: 'numeric', month: 'long', timeZone: 'UTC' });
const dayFmt = new Intl.DateTimeFormat('nl-NL', { day: 'numeric', month: 'long', timeZone: 'UTC' });

/** "maandag 5 oktober" */
export const formatDayLong = (day) => dayLongFmt.format(new Date(`${day}T12:00:00Z`));
/** "5 oktober" */
export const formatDay = (day) => dayFmt.format(new Date(`${day}T12:00:00Z`));

// ---------------------------------------------------------------------------
// Allergenen en eetstijl: dezelfde controle als App\Support\Plans\Allergens (de regels komen van de server),
// zodat de waarschuwingen bij elke wijziging in de editor meteen bijwerken.

const LETTER = /\p{L}/u;

function findTerm(haystack, term, wholeWord) {
    const hits = [];
    let from = 0;
    for (;;) {
        const index = haystack.indexOf(term, from);
        if (index === -1) return hits;
        from = index + term.length;
        if (index > 0 && LETTER.test(haystack[index - 1])) continue;
        let end = index + term.length;
        while (end < haystack.length && LETTER.test(haystack[end])) end++;
        if (wholeWord && end !== index + term.length) continue;
        hits.push({ index, word: haystack.slice(index, end) });
    }
}

function isNeutralized(check, haystack, index, word, rule, diet) {
    if (word.includes('vrij') || word.includes('vervanger')) return true;
    if ([...check.notAMatch, ...(rule.except ?? [])].some((w) => word.startsWith(w))) return true;
    if (/^\s*-?(?:vervanger|free\b|substitute)/.test(haystack.slice(index + word.length))) return true;
    // Alleen de twee woorden direct ervoor tellen mee ("zonder noten", "lactosevrije kwark").
    const previous = haystack
        .slice(Math.max(0, index - 40), index)
        .split(/[^\p{L}-]+/u)
        .filter(Boolean)
        .slice(-2);
    const markers = [...check.absent, ...(rule.freeFrom ?? []), ...(diet ? check.substitute : [])];
    return previous.some((w) => markers.some((m) => w.startsWith(m)));
}

function matchRule(check, haystack, rule, diet) {
    for (const [terms, whole] of [
        [rule.words ?? [], true],
        [rule.prefixes ?? [], false],
    ]) {
        for (const term of terms) {
            for (const hit of findTerm(haystack, term, whole)) {
                if (!isNeutralized(check, haystack, hit.index, hit.word, rule, diet)) return hit.word;
            }
        }
    }
    return null;
}

function nutritionTexts(plan) {
    const texts = [];
    plan.meals.forEach((meal, i) => {
        const mealName = meal.name || `Maaltijd ${i + 1}`;
        meal.options.forEach((option, j) => {
            texts.push({ where: `${mealName} › ${option.title || `optie ${j + 1}`}`, text: `${option.title} ${option.ingredients}` });
        });
    });
    plan.tips.forEach((tip, i) => texts.push({ where: `Tip ${i + 1}`, text: tip }));
    texts.push({ where: 'Toelichting', text: plan.summary });
    // "avoid" wordt bewust overgeslagen: daar horen allergenen juist genoemd te worden.
    return texts;
}

/** check: { rules: [{ rule, reason, diet }], notAMatch, absent, substitute } (App\Services\Plans\PlanView::allergenCheck). */
export function findAllergenWarnings(plan, check) {
    const warnings = [];
    for (const { where, text } of nutritionTexts(plan)) {
        const haystack = text.toLowerCase();
        for (const { rule, reason, diet } of check.rules) {
            const term = matchRule(check, haystack, rule, diet);
            if (term && !warnings.some((w) => w.where === where && w.reason === reason)) warnings.push({ term, reason, where });
        }
    }
    return warnings;
}
