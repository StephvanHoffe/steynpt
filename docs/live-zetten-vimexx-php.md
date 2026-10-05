# De website live zetten bij Vimexx (webhosting, PHP)

Dit stappenplan zet de nieuwe SteynPT-site online op **www.steynpt.nl** met een gewoon **Vimexx-webhostingpakket** (bijvoorbeeld *Webhosting Basic*). Daarvoor is de site in PHP gebouwd (map `php/`, Laravel). Je hebt dus géén VPS nodig.

Om uit te printen of door te sturen staat hetzelfde plan in de huisstijl in [SteynPT - Stappenplan website online.pdf](SteynPT%20-%20Stappenplan%20website%20online.pdf) (bron: `docs/stappenplan-pdf/`).

Het plan bestaat uit twee fases:

- **Fase 1 – Klaarzetten** (± 1 uur). De huidige website blijft al die tijd gewoon online.
- **Fase 2 – Overstappen** (± 15 minuten). De nieuwe site gaat live. Gaat er iets mis, dan staat de oude site met één commando terug.

```
bezoeker ──▶ www.steynpt.nl ──▶ Vimexx-webhosting (DirectAdmin)
                                ├─ public_html  →  koppeling naar steynpt/public
                                ├─ steynpt/     de nieuwe site (PHP 8.3)
                                ├─ MySQL-database
                                └─ cronjob: elke minuut (AI-concepten, ingeplande schema's, nachtelijke back-up)
```

## Wat je nodig hebt

| | |
| --- | --- |
| **Hostingpakket** | Vimexx-webhosting met DirectAdmin, PHP 8.3, een MySQL-database, SSH en cronjobs (zit allemaal in *Webhosting Basic*), plus je inloggegevens voor DirectAdmin en Mijn Vimexx. |
| **Het pakket** | Het bestand `steynpt-php-<datum>.zip` (27 MB). Daar zit alles in; op de hosting is alleen PHP nodig. Een nieuw pakket maak je met `bash deploy/maak-pakket.sh` in de map `php/` (daarvoor zijn PHP 8.3, Composer en Node.js nodig). |
| **Terminal** | Voor SSH. Op Mac: *Terminal*. Op Windows: *PowerShell* of *Windows Terminal* (of PuTTY). |
| **Authenticator-app** | Voor Steyn, bijvoorbeeld Google Authenticator, Microsoft Authenticator of 1Password. Inloggen gaat met tweestapsverificatie. |
| **API-sleutel** (mag later) | Voor de AI-schema's: Steyn maakt zelf een sleutel aan op [platform.claude.com](https://platform.claude.com) en zet daar een maandlimiet. Zonder sleutel werkt alles, alleen maakt Steyn de schema's dan zelf. |

In de voorbeelden staat `GEBRUIKER` voor je DirectAdmin-gebruikersnaam en `steynpt.nl` voor het domein. Tekst na een `#` is uitleg en hoef je niet over te nemen.

**Waar staat de huidige site?** Draait de huidige (WordPress-)site op ditzelfde Vimexx-pakket, dan hoef je aan het domein niets te veranderen: in stap 8 wissel je alleen de map. Staat de huidige site ergens anders, of heb je een nieuw pakket? Dan zet je in stap 9 ook het domein om.

---

# Fase 1 – Klaarzetten

Niets in deze fase raakt de huidige website.

## Stap 1. SSH aanzetten en inloggen

1. In DirectAdmin: **Geavanceerde functies** (*Advanced Features*) **→ SSH-Keys**, en zet rechtsboven **SSH aan** ([uitleg van Vimexx](https://www.vimexx.nl/help/ssh-toegang-inschakelen)).
2. Verbind vanaf je computer. Het wachtwoord is je DirectAdmin-wachtwoord; wijst het domein nog niet naar Vimexx, gebruik dan het IP-adres van het pakket in plaats van `steynpt.nl`.

   ```bash
   ssh GEBRUIKER@steynpt.nl -p 7685
   ```

3. Controleer PHP 8.3 voor de commandoregel. Bij Vimexx staat elke versie op een eigen plek:

   ```bash
   /opt/alt/php83/usr/bin/php -v          # moet "PHP 8.3" tonen
   /opt/alt/php83/usr/bin/php -m | grep -i -E "pdo_mysql|mbstring|openssl|fileinfo"   # vier regels
   alias php=/opt/alt/php83/usr/bin/php   # zodat "php" in deze sessie PHP 8.3 is
   ```

   Log je later opnieuw in via SSH, typ dan eerst weer die `alias`-regel.

## Stap 2. Database aanmaken

1. In DirectAdmin: **MySQL-beheer** (*MySQL Management*) **→ Nieuwe database maken**.
2. Kies een naam (bijv. `steynpt`) en laat DirectAdmin een sterk wachtwoord maken.
3. Noteer de **databasenaam**, **gebruikersnaam** en **wachtwoord** (de namen beginnen met `GEBRUIKER_`).

Laat een eventuele WordPress-database staan; die is je terugvaloptie.

## Stap 3. Het pakket uploaden en uitpakken

1. In DirectAdmin: **Bestandsbeheer** (*File Manager*) → ga naar `domains/steynpt.nl/` (de map waar ook `public_html` in staat) en upload `steynpt-php-<datum>.zip`.
2. Pak het uit via SSH (of met *Uitpakken* in Bestandsbeheer):

   ```bash
   cd ~/domains/steynpt.nl
   unzip -q steynpt-php-*.zip      # maakt de map steynpt/
   rm steynpt-php-*.zip
   ```

`public_html` (de huidige site) blijft nog ongemoeid.

## Stap 4. Instellingen (.env)

```bash
cd ~/domains/steynpt.nl/steynpt
cp .env.example .env
nano .env
```

Vul in (de rest kan blijven staan):

| Instelling | Waarde |
| --- | --- |
| `APP_URL` | `https://www.steynpt.nl` |
| `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | uit stap 2 (`DB_HOST=localhost` laten staan) |
| `ADMIN_EMAILS` | `steyn@steynpt.nl` (dit adres wordt automatisch beheerder) |
| `ANTHROPIC_API_KEY` | de sleutel van platform.claude.com (mag ook later) |

Opslaan in nano: `Ctrl+O`, `Enter`, `Ctrl+X`. Daarna:

```bash
php artisan key:generate --force     # geheime sleutel voor sessies en versleuteling
php artisan migrate --force          # tabellen aanmaken (typ niets, dit gaat vanzelf)
php artisan optimize                 # instellingen, routes en pagina's voorbereiden
```

> Pas je later iets aan in `.env`, draai dan opnieuw `php artisan optimize`. Anders blijft de oude instelling actief.

## Stap 5. Cronjob

Eén cronjob doet alles wat op de achtergrond moet: AI-concepten maken, ingeplande schema's op hun startdag zichtbaar maken en elke nacht een back-up van de database.

In DirectAdmin: **Geavanceerde functies** (*Advanced Features*) **→ Cronjobs** ([uitleg van Vimexx](https://www.vimexx.nl/help/hoe-maak-ik-een-cronjob-aan)). Vul bij alle tijdvelden `*` in (= elke minuut), vink **Prevent Email** aan, en gebruik als commando:

```
cd /home/GEBRUIKER/domains/steynpt.nl/steynpt && /opt/alt/php83/usr/bin/php artisan schedule:run >/dev/null 2>&1
```

Alles draait binnen dit ene PHP-proces; de hosting hoeft geen andere programma's te kunnen starten. Voor de AI-concepten moet de server wel naar `api.anthropic.com` kunnen (een gewone https-verbinding).

Controle (na een minuut of twee, via SSH):

```bash
cd ~/domains/steynpt.nl/steynpt && php artisan schedule:list   # drie taken: schemas-publiceren, back-up en wachtrij
php artisan steynpt:backup                                     # maakt nu meteen een back-up; moet "Back-up gemaakt" melden
```

---

# Fase 2 – Overstappen

Kies hiervoor een rustig moment. De site is hooguit een paar minuten niet bereikbaar.

## Stap 6. Een kopie van de huidige site

Maak in DirectAdmin een back-up van het account (**Back-up maken / herstellen** → *Back-up maken*), of download in elk geval de map `public_html`. De oude site blijft daarnaast als map `public_html-oud` op de hosting staan (stap 8).

## Stap 7. PHP 8.3 aanzetten

Vimexx kiest de PHP-versie per domein met een `.htaccess`-bestand ([uitleg van Vimexx](https://www.vimexx.com/help/hoe-stel-ik-de-php-versie-per-domeinnaam-in)). Maak in **Bestandsbeheer** in `domains/steynpt.nl/` (naast `public_html`, niet erin) een bestand `.htaccess` met:

```apache
<FilesMatch "\.(php|phtml)$">
    SetHandler application/x-lsphp83
</FilesMatch>
```

Staat daar al een `.htaccess`? Zet deze regels dan bovenaan en laat de rest staan.

## Stap 8. De nieuwe site aanzetten

Laat `public_html` naar de nieuwe site wijzen (zo installeert [Vimexx zelf Laravel](https://www.vimexx.nl/help/hoe-installeer-ik-het-laravel-framework-op-mijn-website)). Via SSH:

```bash
cd ~/domains/steynpt.nl
mv public_html public_html-oud           # de oude site bewaren
ln -s steynpt/public public_html          # de nieuwe site aanzetten
```

Wees precies: bij een typfout in de tweede regel verschijnt de site niet. Controleer met `ls -l public_html`; je ziet dan `public_html -> steynpt/public`.

## Stap 9. Domein (alleen als dat nodig is)

Draaide de oude site al op dit pakket, sla deze stap dan over: www.steynpt.nl toont nu de nieuwe site.

Stond de oude site ergens anders, zet dan in **Mijn Vimexx → Mijn domeinen → steynpt.nl → DNS** de A-records van `steynpt.nl` en `www` op het IP-adres van het hostingpakket (in DirectAdmin te vinden bij de *Accountinformatie*). Laat de MX-records (e-mail) ongemoeid. Meestal is de wijziging binnen een uur zichtbaar; ga daarna verder met stap 10.

## Stap 10. HTTPS

De site werkt alleen via https: inloggen gebruikt beveiligde cookies.

1. In DirectAdmin: **SSL-certificaten** (*SSL Certificates*). Is er al een geldig certificaat voor `steynpt.nl` en `www.steynpt.nl` (bijvoorbeeld van de oude site), dan is dat genoeg. Anders: kies **Let's Encrypt** voor beide namen.
2. Zet bij **Domeinbeheer** (*Domain Setup*) **→ steynpt.nl** het vinkje **Forceer SSL met https-omleiding** (*Force SSL with https redirect*) aan.

## Stap 11. Controleren en inrichten

1. Open **https://www.steynpt.nl** en klik een paar pagina's door. Een oud adres zoals `/over-steynpt` of `/kennismaking` moet doorsturen naar de nieuwe pagina.
2. Steyn maakt via **Account aanmaken** het account `steyn@steynpt.nl` aan (dat wordt automatisch beheerder) en koppelt de authenticator-app. **Bewaar de herstelcodes** op een veilige plek.
3. In **Beheer → Instellingen**: stel de beschikbaarheid voor de agenda in en zet de **iCal-link** in de agenda-app van Steyn.
4. In **Beheer → Website-teksten**: loop de teksten en prijzen na.
5. Doe zelf een proef als klant: maak een tweede account aan, boek een afspraak en zeg hem weer af.
6. Optioneel: meld `https://www.steynpt.nl/sitemap.xml` aan bij Google Search Console.

Werkt alles, dan kun je na een paar weken `public_html-oud` en de oude WordPress-database verwijderen.

### Terug naar de oude site

Gaat er bij het overstappen iets mis, zet dan via SSH de oude site terug:

```bash
cd ~/domains/steynpt.nl
rm public_html                       # verwijdert alleen de koppeling, niet de nieuwe site
mv public_html-oud public_html
```

Haal ook de regels uit stap 7 weer uit `domains/steynpt.nl/.htaccess` als de oude site een andere PHP-versie nodig had.

---

## Een nieuwe versie zetten

1. Upload het nieuwe `steynpt-php-<datum>.zip` naar `domains/steynpt.nl/`.
2. Via SSH:

   ```bash
   alias php=/opt/alt/php83/usr/bin/php
   cd ~/domains/steynpt.nl
   php steynpt/artisan steynpt:backup              # eerst een back-up
   unzip -q steynpt-php-*.zip -d nieuw && rm steynpt-php-*.zip
   cp steynpt/.env nieuw/steynpt/.env
   cp -a steynpt/storage/backups/. nieuw/steynpt/storage/backups/
   mv steynpt steynpt-vorige && mv nieuw/steynpt steynpt && rmdir nieuw
   cd steynpt && php artisan migrate --force && php artisan optimize
   ```

   `public_html` blijft naar `steynpt/public` wijzen, dus de nieuwe versie staat direct online.

3. Gaat er iets mis? Zet de vorige versie terug:

   ```bash
   cd ~/domains/steynpt.nl && mv steynpt steynpt-kapot && mv steynpt-vorige steynpt
   ```

   Werkt alles, verwijder dan `steynpt-vorige` (en eventueel `steynpt-kapot`).

## Back-ups

- Elke nacht om 03:15 maakt de cronjob een back-up van de database in `steynpt/storage/backups/` (30 dagen bewaard). Handmatig: `php artisan steynpt:backup`.
- Download af en toe een back-up naar je eigen computer (Bestandsbeheer of `scp -P 7685`).
- Terugzetten: `gunzip -c storage/backups/steynpt-<datum>.sql.gz | mysql -u DB_GEBRUIKER -p DB_NAAM`.

## Noodgevallen

| Probleem | Oplossing |
| --- | --- |
| Een melding over de PHP-versie, of een lege pagina | Controleer stap 7 (`.htaccess` naast `public_html`) en stap 8 (`ls -l public_html`). |
| Foutmelding "Er ging iets mis" (500) | Kijk in `steynpt/storage/logs/` (laatste bestand). Zet nooit `APP_DEBUG=true` op de live site. |
| Inloggen lukt niet (pagina verlopen) | Controleer of de site via https draait (stap 10) en of `APP_URL` met `https://` begint; draai daarna `php artisan optimize`. |
| AI-concepten blijven op "AI is bezig" staan | Controleer de cronjob (stap 5) met `php artisan schedule:list`. Draait die wel, vraag Vimexx dan of uitgaande verbindingen naar `api.anthropic.com` zijn toegestaan. Na tien minuten kan Steyn opnieuw laten genereren. |
| Steyn is zijn telefoon én herstelcodes kwijt | Via SSH: `php artisan steynpt:reset-2fa steyn@steynpt.nl`. Bij de volgende keer inloggen koppelt hij een nieuwe telefoon. |
