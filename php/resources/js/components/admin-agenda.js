// Agenda in het beheer: het formulier om een afspraak in te plannen of te verplaatsen.
import Alpine from 'alpinejs';

/**
 * Soort afspraak bepaalt welke locaties er zijn: bij een ander type worden de opties van de locatiekeuze
 * opnieuw opgebouwd. De gekozen locatie blijft staan als die ook bij het nieuwe type kan.
 */
Alpine.data('adminAppointmentForm', ({ types, locations, typeId, locationId }) => ({
    typeId,
    locationId,

    get type() {
        return types.find((t) => t.id === this.typeId) ?? types[0];
    },

    get locations() {
        return locations.filter((l) => this.type.locations.includes(l.id));
    },

    get location() {
        return this.locations.some((l) => l.id === this.locationId) ? this.locationId : this.locations[0].id;
    },

    init() {
        this.$watch('typeId', () => this.renderLocations());
    },

    renderLocations() {
        const select = this.$refs.location;
        if (!select) return;
        select.replaceChildren(
            ...this.locations.map((l) => {
                const option = document.createElement('option');
                option.value = l.id;
                option.textContent = l.label;
                option.selected = l.id === this.location;
                return option;
            }),
        );
        select.value = this.location;
    },
}));

// Afspraak openen of het paneel sluiten laadt de agenda opnieuw: blijf op dezelfde plek staan
// (zoals scroll={false} in de Next.js-versie), ook binnen het tijdrooster.
const SCROLL_KEY = 'beheer-agenda-scroll';

document.addEventListener('click', (e) => {
    const link = e.target?.closest?.('a[data-keep-scroll]');
    if (!link || e.defaultPrevented || e.metaKey || e.ctrlKey || e.shiftKey) return;
    const grid = document.querySelector('[data-time-grid]');
    try {
        window.sessionStorage.setItem(SCROLL_KEY, JSON.stringify({ x: window.scrollX, y: window.scrollY, top: grid?.scrollTop ?? 0, left: grid?.scrollLeft ?? 0 }));
    } catch {
        // Geen opslag: dan begint de pagina gewoon bovenaan.
    }
});

(() => {
    let saved = null;
    try {
        saved = JSON.parse(window.sessionStorage.getItem(SCROLL_KEY) ?? 'null');
        window.sessionStorage.removeItem(SCROLL_KEY);
    } catch {
        return;
    }
    if (!saved || !location.pathname.startsWith('/admin/agenda')) return;
    const grid = document.querySelector('[data-time-grid]');
    if (grid) {
        grid.scrollTop = saved.top;
        grid.scrollLeft = saved.left;
    }
    window.scrollTo({ left: saved.x, top: saved.y, behavior: 'instant' });
})();
