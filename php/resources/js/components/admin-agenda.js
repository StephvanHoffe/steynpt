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
