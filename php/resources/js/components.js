// Alpine-componenten: elk bestand in resources/js/components/ registreert zijn eigen onderdelen
// met Alpine.data(...) (Alpine staat op window.Alpine en start pas na het laden van deze bestanden).
import Alpine from 'alpinejs';

window.Alpine = Alpine;
import.meta.glob('./components/*.js', { eager: true });
