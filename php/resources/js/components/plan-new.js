// Formulier "Nieuw schema": startdatum (met snelkeuzes) en hoe je wilt beginnen (AI, kopie of leeg).
import Alpine from 'alpinejs';
import { formatDayLong } from './plan-utils.js';

Alpine.data('newPlanForm', (config) => ({
    start: config.defaultStart,
    method: config.defaultMethod,

    get later() {
        return this.start > config.today;
    },

    get startLabel() {
        return formatDayLong(this.start);
    },

    get submitLabel() {
        return config.submitLabels[this.method];
    },

    setStart(day) {
        if (day) this.start = day;
    },
}));
