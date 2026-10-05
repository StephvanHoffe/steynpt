// Kopieerknop in het beheer (bijvoorbeeld de iCal-link).
import Alpine from 'alpinejs';

Alpine.data('adminCopyField', (value, label) => ({
    copied: false,

    async copy() {
        try {
            await navigator.clipboard.writeText(value);
            this.copied = true;
            setTimeout(() => (this.copied = false), 2000);
        } catch {
            window.prompt(label, value);
        }
    },
}));
