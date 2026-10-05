# De website live zetten bij Vimexx (webhosting, PHP)

Dit stappenplan zet de SteynPT-site online op **www.steynpt.nl** met een gewoon **Vimexx-webhostingpakket** (bijvoorbeeld *Webhosting Basic*). Daarvoor is de site in PHP gebouwd (map `php/`, Laravel). Je hebt dus géén VPS nodig. Reken voor de eerste keer op een uur of twee.

```
bezoeker ──▶ www.steynpt.nl ──▶ Vimexx-webhosting (DirectAdmin)
                                ├─ public_html  →  koppeling naar steynpt/public
                                ├─ steynpt/     de site (PHP 8.3)
                                ├─ MySQL-database
                                └─ cronjob: elke minuut (AI-concepten, ingeplande schema's, nachtelijke back-up)
```

## Wat je nodig hebt

| | |
| --- | --- |
| **Hostingpakket** | Vimexx webhosting met DirectAdmin, PHP 8.3, een MySQL-database, SSH en cronjobs (zit allemaal in *Webhosting Basic*). |
| **Het pakket** | Het bestand `steynpt-php-<datum>.zip`. Maak het op je eigen computer met `bash deploy/maak-pakket.sh` in de map `php/` (daarvoor zijn PHP 8.3, Composer en Node.js nodig), of vraag het aan degene die de site bouwt. Er zit alles in; op de hosting is alleen PHP nodig. |
| **Terminal** | Voor SSH. Op Mac: *Terminal*. Op Windows: *PowerShell* of *Windows Terminal* (of PuTTY). |
| **API-sleutel** | Voor de AI-schema's: Steyn maakt zelf een sleutel aan op [platform.claude.com](https://platform.claude.com) en zet daar een maandlimiet. Zonder sleutel werkt alles, alleen maakt Steyn de schema's dan zelf. |
| **Authenticator-app** | Voor Steyn, bijvoorbeeld Google Authenticator, Microsoft Authenticator of 1Password. Inloggen gaat met tweestapsverificatie. |

In de voorbeelden staat `GEBRUIKER` voor je DirectAdmin-gebruikersnaam en `steynpt.nl` voor het domein. Regels die met `#` beginnen zijn uitleg.

---

## Stap 1. PHP 8.3 instellen

Vimexx kiest de PHP-versie per domein met een `.htaccess`-bestand ([uitleg van Vimexx](https://www.vimexx.com/help/hoe-stel-ik-de-php-versie-per-domeinnaam-in)).

1. Log in op DirectAdmin en open **Bestandsbeheer** (*File Manager*).
2. Ga naar `domains/steynpt.nl/` (de map *boven* `public_html`).
3. Maak daar een bestand `.htaccess` met deze inhoud:

   ```apache
   <FilesMatch "\.(php|phtml)$">
       SetHandler application/x-lsphp83
   </FilesMatch>
   ```

## Stap 2. SSH aanzetten

1. In DirectAdmin: **Geavanceerde functies** (*Advanced Features*) **→ SSH-Keys**, en zet rechtsboven **SSH aan** ([uitleg](https://www.vimexx.nl/help/ssh-toegang-inschakelen)).
2. Verbind vanaf je computer (wachtwoord = je DirectAdmin-wachtwoord):

   ```bash
   ssh GEBRUIKER@steynpt.nl -p 7685
   ```

3. Controleer PHP voor de commandoregel. Bij Vimexx staat elke versie op een eigen plek; gebruik in alle commando's hieronder het volledige pad:

   ```bash
   /opt/alt/php83/usr/bin/php -v          # moet "PHP 8.3" tonen
   /opt/alt/php83/usr/bin/php -m | grep -i -E "pdo_mysql|mbstring|openssl|fileinfo"   # vier regels
   ```

   Om het jezelf makkelijk te maken in deze SSH-sessie:

   ```bash
   alias php=/opt/alt/php83/usr/bin/php
   ```

## Stap 3. Database aanmaken

1. In DirectAdmin: **MySQL-beheer** (*MySQL Management*) **→ Nieuwe database maken**.
2. Kies een naam (bijv. `steynpt`) en laat DirectAdmin een sterk wachtwoord maken. Noteer de **databasenaam**, **gebruikersnaam** en **wachtwoord** (de namen beginnen met `GEBRUIKER_`).

## Stap 4. Het pakket uploaden en uitpakken

1. In **Bestandsbeheer**: ga naar `domains/steynpt.nl/` en upload `steynpt-php-<datum>.zip`.
2. Pak het uit via SSH (of met *Uitpakken* in Bestandsbeheer):

   ```bash
   cd ~/domains/steynpt.nl
   unzip -q steynpt-php-*.zip      # maakt de map steynpt/
   rm steynpt-php-*.zip
   ```

3. Laat `public_html` naar de site wijzen (dit is ook hoe [Vimexx Laravel installeert](https://www.vimexx.nl/help/hoe-installeer-ik-het-laravel-framework-op-mijn-website)). Staat er nog een oude site in `public_html`? Bewaar die eerst:

   ```bash
   mv public_html public_html-oud     # of: rm -rf public_html als hij leeg is
   ln -s steynpt/public public_html
   ```

## Stap 5. Instellingen (.env)

```bash
cd ~/domains/steynpt.nl/steynpt
cp .env.example .env
nano .env
```

Vul in (de rest kan blijven staan):

| Instelling | Waarde |
| --- | --- |
| `APP_URL` | `https://www.steynpt.nl` |
| `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | uit stap 3 (`DB_HOST=localhost`) |
| `ADMIN_EMAILS` | `steyn@steynpt.nl` (dit adres wordt automatisch beheerder) |
| `ANTHROPIC_API_KEY` | de sleutel van platform.claude.com (mag later) |

Opslaan in nano: `Ctrl+O`, `Enter`, `Ctrl+X`. Daarna:

```bash
php artisan key:generate --force     # geheime sleutel voor sessies en versleuteling
php artisan migrate --force          # tabellen aanmaken
php artisan optimize                 # instellingen, routes en pagina's voorbereiden (sneller)
```

> Pas je later iets aan in `.env`, draai dan opnieuw `php artisan optimize`. Anders blijft de oude instelling actief.

## Stap 6. HTTPS

1. In DirectAdmin: **SSL-certificaten** (*SSL Certificates*) → kies **Let's Encrypt** voor `steynpt.nl` en `www.steynpt.nl`.
2. Zet bij **Domeinbeheer** (*Domain Setup*) **→ steynpt.nl** het vinkje **Forceer SSL met https-omleiding** (*Force SSL with https redirect*) aan.

De site werkt alleen via https: inloggen gebruikt beveiligde cookies (`SESSION_SECURE_COOKIE=true`).

## Stap 7. Cronjob

Eén cronjob doet alles wat op de achtergrond moet: de AI-concepten maken, ingeplande schema's op hun startdag zichtbaar maken en elke nacht een back-up van de database.

In DirectAdmin: **Geavanceerde functies** (*Advanced Features*) **→ Cronjobs** ([uitleg](https://www.vimexx.nl/help/hoe-maak-ik-een-cronjob-aan)). Vul bij alle tijdvelden `*` in (= elke minuut) en als commando:

```
cd /home/GEBRUIKER/domains/steynpt.nl/steynpt && /opt/alt/php83/usr/bin/php artisan schedule:run >/dev/null 2>&1
```

Vink **Prevent Email** aan.

## Stap 8. Controleren en Steyn als beheerder

1. Open **https://www.steynpt.nl**. Zie je een melding over de PHP-versie, controleer dan stap 1.
2. Maak via **Account aanmaken** het account `steyn@steynpt.nl` aan (dat wordt beheerder) en koppel de authenticator-app. **Bewaar de herstelcodes** op een veilige plek.
3. Ga naar **Beheer → Instellingen** en stel de beschikbaarheid voor de agenda in.
4. Kopieer daar de **iCal-link** naar de agenda-app van Steyn.
5. Controleer na een paar minuten via SSH of de cronjob draait:

   ```bash
   cd ~/domains/steynpt.nl/steynpt && php artisan schedule:list
   ls storage/backups      # na de eerste nacht staat hier een back-up
   ```

## Stap 9. Domein (DNS)

Staat het domein bij Vimexx en is het gekoppeld aan dit hostingpakket, dan hoef je niets te doen. Wijst het domein nog naar de oude website (bijvoorbeeld een andere hosting), zet dan in **Mijn Vimexx → Mijn domeinen → steynpt.nl → DNS** de A-records van `steynpt.nl` en `www` op het IP-adres van het hostingpakket (te vinden in DirectAdmin, rechts bij *Accountinformatie*). Wijzigingen zijn meestal binnen een uur zichtbaar. Laat de MX-records (e-mail) ongemoeid.

De oude WordPress-adressen (zoals `/over-steynpt` en `/kennismaking`) sturen automatisch door naar de nieuwe pagina's.

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
| Steyn is zijn telefoon én herstelcodes kwijt | Via SSH: `php artisan steynpt:reset-2fa steyn@steynpt.nl`. Bij de volgende keer inloggen koppelt hij een nieuwe telefoon. |
| Foutmelding "Er ging iets mis" (500) | Kijk in `steynpt/storage/logs/` (laatste bestand). Zet nooit `APP_DEBUG=true` op de live site. |
| AI-concepten blijven op "AI is bezig" staan | De cronjob draait niet: controleer stap 7 en `php artisan schedule:list`. Na tien minuten kan Steyn opnieuw laten genereren. |
| Inloggen lukt niet (pagina verlopen) | Controleer of de site via https draait (stap 6) en of `APP_URL` met `https://` begint. |
