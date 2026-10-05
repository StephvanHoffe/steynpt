# SteynPT – PHP-versie (Laravel)

Dezelfde website als de Next.js-versie in de hoofdmap, maar in PHP, zodat hij op gewone webhosting draait (zoals Vimexx Webhosting met DirectAdmin). Pagina's, teksten, opmaak, URL's en formulieren zijn gelijk; de bestaande end-to-end-tests draaien tegen beide versies.

**Live zetten:** volg [docs/live-zetten-vimexx-php.md](../docs/live-zetten-vimexx-php.md).

## Nederlands en Engels

De site is tweetalig (alleen in deze PHP-versie, niet in de Next.js-versie):

- Openbare pagina's hebben een Nederlands en een Engels adres (`/tarieven` en `/en/pricing`, zie `App\Site\Locale::PATHS`), met hreflang-links en beide talen in de sitemap. De taalknop (NL/EN) staat rechtsboven en in de footer.
- Inloggen, registreren en Mijn omgeving hebben één adres en volgen de taal van het lid (`users.locale`, ook te kiezen in het profiel) of, zonder account, de laatst gekozen taal (cookie `taal`). AI-schema's worden in de taal van het lid gemaakt. Het beheer is altijd Nederlands.
- Teksten uit Website-teksten hebben een Engelse versie (`app/Content/English.php`) die Steyn in het beheer per pagina kan aanpassen (knop *English*). Prijzen, links, het adres en de contactgegevens zijn in beide talen gelijk en stel je in het Nederlands in.
- Vaste teksten in de code staan in `__('Nederlandse tekst')`; de Engelse vertalingen staan in `lang/json/*/en.json` (zie `lang/json/README.md`).

## Techniek

- Laravel 13, PHP 8.3, MySQL/MariaDB (lokaal kan ook SQLite).
- Blade-templates met dezelfde HTML en Tailwind-klassen als de React-componenten; Alpine.js voor de interactieve onderdelen (menu, grafieken, editors).
- CSS en JavaScript worden vooraf gebouwd met Vite (`npm run build` → `public/build`), zodat de server alleen PHP nodig heeft.
- Tijden staan in UTC in de database en worden getoond in Europe/Amsterdam; dagen zijn `YYYY-MM-DD`-tekst.
- Achtergrondwerk (AI-concepten, ingeplande schema's activeren, nachtelijke back-up) loopt via één cronjob: `php artisan schedule:run` elke minuut.

## Mappen

| Map | Inhoud |
| --- | --- |
| `app/Content` | Register van de website-teksten (standaardteksten per pagina, controle, `*accent*`-opmaak, automatische waarden). |
| `app/Support` | Pure logica, 1-op-1 overgezet uit `src/lib` en getest tegen de uitkomsten van de TypeScript-code: agenda en tijdsloten, tweestapsverificatie, intake, voortgang, schema-fases, AI-prompt en schema-controle. |
| `app/Services` | Logica met de database: agenda (vrije tijden, iCal), schema-overzicht, tellers voor het beheer, AI-generatie. |
| `app/Auth` | Inloggen met tweestapsverificatie, herstelcodes, wachtwoordtermijn van 8 weken. |
| `app/Http/Controllers` | Website, inloggen, Mijn omgeving (`Account`), beheer (`Admin`). |
| `resources/views` | Pagina's en componenten (Blade). |
| `deploy/maak-pakket.sh` | Maakt het installatiepakket (zip met `vendor/` en `public/build`). |

## Lokaal draaien

```bash
composer install
npm install && npm run build
cp .env.example .env            # zet APP_ENV=local, APP_DEBUG=true, SESSION_SECURE_COOKIE=false, AI_MOCK=1
php artisan key:generate
php artisan migrate
php artisan serve
```

Met `ADMIN_EMAILS=jouw@adres.nl` in `.env` word je beheerder zodra je een account aanmaakt.

## Handige commando's

| Commando | |
| --- | --- |
| `php artisan test` | PHPUnit (eenheden en features, op SQLite in het geheugen). |
| `php artisan steynpt:backup` | Back-up van de database in `storage/backups` (30 dagen). |
| `php artisan steynpt:reset-2fa <e-mail>` | Noodgeval: tweestapsverificatie van een account uitzetten. |
| `php artisan schedule:list` | Wat de cronjob doet en wanneer. |
| `bash deploy/maak-pakket.sh` | Installatiepakket maken in `dist/`. |
| `node php/deploy/maak-webp.mjs` | Na een nieuwe foto in `public/images`: lichtere WebP-versies maken (vanuit de hoofdmap; `<x-photo>` gebruikt ze vanzelf). |
