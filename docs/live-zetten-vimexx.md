# De website live zetten bij Vimexx

Dit stappenplan zet de nieuwe SteynPT-site online op **www.steynpt.nl**, op een VPS bij Vimexx. Reken voor de eerste keer op een middag. Daarna kost een update één commando.

## Waarom een VPS?

De nieuwe site is geen WordPress, maar een Node.js-applicatie met een eigen database (accounts, agenda, schema's, website-teksten). De gewone webhosting van Vimexx (met DirectAdmin) kan geen Node.js draaien. Daarvoor heb je een **VPS** nodig: een eigen virtuele server.

Het domein en de e-mail kunnen blijven waar ze nu zijn. Alleen het adres van de website gaat naar de VPS wijzen.

```
bezoeker ──▶ www.steynpt.nl ──▶ VPS bij Vimexx
             (DNS bij Vimexx)    ├─ Caddy: HTTPS-certificaat en doorsturen
                                 ├─ de site (Node.js, poort 3000)
                                 └─ database-bestand + dagelijkse back-ups
```

## Wat je nodig hebt

| | |
| --- | --- |
| **VPS** | Vimexx *Budget VPS Entry M* (2 cores, 4 GB, € 14,99 p/m) is de aanrader. *Entry S* (1 core, 2 GB, € 6,99 p/m) kan ook, met extra swapgeheugen (stap 3). Het bouwen van de site vraagt ongeveer 1,5 GB geheugen; de draaiende site zelf ongeveer 200 MB. |
| **Toegang** | Inloggen bij Mijn Vimexx (domein en DNS) en bij GitHub (de code). |
| **Terminal** | Op Mac: *Terminal*. Op Windows: *PowerShell* of *Windows Terminal*. |
| **API-sleutel** | Voor de AI-schema's: Steyn maakt zelf een sleutel aan op [platform.claude.com](https://platform.claude.com). Zet daar ook een maandlimiet. Zonder sleutel werkt alles, alleen maakt Steyn de schema's dan zelf. |
| **Authenticator-app** | Voor Steyn, bijvoorbeeld Google Authenticator, Microsoft Authenticator of 1Password. Inloggen gaat met tweestapsverificatie. |

> Liever niet zelf het serverbeheer doen? Vimexx heeft ook een **Managed VPS** (vanaf € 17,50 p/m), waarbij zij updates en beveiliging van de server regelen. Vraag ze dan om Node.js 22 en Caddy te installeren (stap 3); de rest van dit stappenplan blijft hetzelfde.

In de voorbeelden hieronder staat `123.45.67.89` voor het IP-adres van je VPS. Regels die met `#` beginnen zijn uitleg en hoef je niet over te nemen.

---

## Stap 1. VPS bestellen

1. Bestel bij Vimexx een *Budget VPS* (Entry M, of Entry S).
2. Kies als besturingssysteem **Ubuntu 24.04 LTS**.
3. Noteer na de levering het **IP-adres** en het **root-wachtwoord** (of zet je eigen SSH-sleutel erop als dat kan).

## Stap 2. De code klaarzetten op GitHub

De code staat in de repository `StephvanHoffe/steynpt`, op de branch `claude/dreamy-brown-cauqyb`. Maak daar een vaste hoofdbranch `main` van, zodat de server altijd die versie ophaalt:

1. Open de repository op GitHub en klik op het branch-menu (links boven de bestanden).
2. Typ `main` en kies **Create branch main from claude/dreamy-brown-cauqyb**.
3. Ga naar **Settings → General → Default branch** en kies `main`.

Nieuwe wijzigingen komen voortaan op `main` en gaan met één commando live (stap 10).

## Stap 3. De server inrichten (eenmalig)

Log in op de server:

```bash
ssh root@123.45.67.89
```

**Updates en basisprogramma's**

```bash
apt update && apt upgrade -y
apt install -y git curl ufw unattended-upgrades
# Beveiligingsupdates voortaan automatisch: kies 'Yes'
dpkg-reconfigure -plow unattended-upgrades
```

**Firewall**: alleen SSH en de website zijn bereikbaar.

```bash
ufw allow OpenSSH
ufw allow 80,443/tcp
ufw enable
```

**Swapgeheugen**: nodig bij Entry S (2 GB), en bij Entry M geen kwaad.

```bash
fallocate -l 2G /swapfile && chmod 600 /swapfile && mkswap /swapfile && swapon /swapfile
echo '/swapfile none swap sw 0 0' >> /etc/fstab
```

**Node.js 22**

```bash
curl -fsSL https://deb.nodesource.com/setup_22.x | bash -
apt install -y nodejs
node -v   # moet v22.… tonen
```

**Caddy**: de webserver die zelf het HTTPS-certificaat regelt.

```bash
apt install -y caddy
```

**Een eigen gebruiker en mappen voor de site**

```bash
adduser --system --group --home /srv/steynpt --shell /bin/bash steynpt
mkdir -p /srv/steynpt/shared /srv/steynpt/data /srv/steynpt/backups /srv/steynpt/releases
chown -R steynpt:steynpt /srv/steynpt
# De site-gebruiker mag de site herstarten, en verder niets met beheerrechten
echo 'steynpt ALL=(root) NOPASSWD: /usr/bin/systemctl restart steynpt' > /etc/sudoers.d/steynpt
chmod 440 /etc/sudoers.d/steynpt
```

> Tip voor later: log in met een SSH-sleutel in plaats van een wachtwoord, en zet daarna inloggen met een wachtwoord uit (`PasswordAuthentication no` in `/etc/ssh/sshd_config`). Zo kan niemand je wachtwoord raden.

## Stap 4. De server toegang geven tot GitHub

De server haalt de code zelf op. Daarvoor krijgt hij een eigen sleutel die alleen mag lezen.

```bash
sudo -iu steynpt
ssh-keygen -t ed25519 -C "steynpt-server" -f ~/.ssh/id_ed25519 -N ""
cat ~/.ssh/id_ed25519.pub
```

Kopieer de regel die verschijnt (`ssh-ed25519 AAAA… steynpt-server`). Ga op GitHub naar **Settings → Deploy keys → Add deploy key**, plak hem erin en laat *Allow write access* uit. Controleer daarna de verbinding en haal de code één keer op:

```bash
ssh -T git@github.com   # bij de vraag over de fingerprint: yes
git clone --depth 1 git@github.com:StephvanHoffe/steynpt.git ~/setup
exit
```

## Stap 5. De instellingen (.env)

Weer als root:

```bash
nano /srv/steynpt/shared/.env
```

Zet hierin, met de echte waarden:

```bash
DATABASE_URL=file:/srv/steynpt/data/steynpt.db
NEXT_PUBLIC_SITE_URL=https://www.steynpt.nl
ADMIN_EMAILS=steyn@voorbeeld.nl
ANTHROPIC_API_KEY=sk-ant-...
```

- `ADMIN_EMAILS` is het e-mailadres waarmee Steyn inlogt. Wie zich met dit adres registreert, krijgt toegang tot het beheer.
- Zet **geen** `DEMO_MODE` of `AI_MOCK` in dit bestand. Die zijn alleen voor de demo en voor testen.

Opslaan doe je met Ctrl+O en Enter, afsluiten met Ctrl+X. Maak het bestand daarna alleen leesbaar voor de site:

```bash
chown steynpt:steynpt /srv/steynpt/shared/.env && chmod 600 /srv/steynpt/shared/.env
```

Wijzig je later iets in `.env`, herstart dan de site met `systemctl restart steynpt`. Voor `NEXT_PUBLIC_SITE_URL` is een nieuwe update nodig (stap 10), omdat dat adres bij het bouwen wordt vastgelegd.

## Stap 6. De site de eerste keer starten

Installeer de service. Die zorgt dat de site start bij het opstarten van de server en herstart na een fout:

```bash
cp /srv/steynpt/setup/deploy/steynpt.service /etc/systemd/system/
systemctl daemon-reload
systemctl enable steynpt
```

Zet de site neer met het deploy-script. Dat duurt een paar minuten:

```bash
sudo -iu steynpt
bash ~/setup/deploy/deploy.sh
exit
```

Het script haalt de code op, installeert alles, maakt de database aan, bouwt de site en start hem. Het eindigt met `✓ Live: …`. De map `~/setup` heb je daarna niet meer nodig (`rm -rf /srv/steynpt/setup`).

Controleer dat de site draait:

```bash
systemctl status steynpt          # moet 'active (running)' tonen
curl -I http://127.0.0.1:3000     # moet 'HTTP/1.1 200 OK' tonen
```

## Stap 7. Eerst testen op een tijdelijk adres

Test de nieuwe site eerst op **nieuw.steynpt.nl**. De oude site blijft dan nog gewoon online.

**DNS.** Voeg bij Vimexx een A-record toe:

| Naam | Type | Inhoud |
| --- | --- | --- |
| `nieuw` | A | `123.45.67.89` |

Dat doe je in Mijn Vimexx onder **Mijn domeinen → steynpt.nl → DNS**. Is het domein gekoppeld aan een webhostingpakket, dan gaat het via DirectAdmin onder **DNS-beheer**. In DirectAdmin kun je een record niet bewerken: verwijder het oude en maak een nieuw aan.

**Caddy.** Zet dit in `/etc/caddy/Caddyfile` (`nano /etc/caddy/Caddyfile`; vervang wat er stond):

```
nieuw.steynpt.nl {
	encode zstd gzip
	reverse_proxy 127.0.0.1:3000
}
```

```bash
systemctl reload caddy
```

Na een paar minuten staat de site op **https://nieuw.steynpt.nl**, met certificaat.

**Testen.**

- [ ] Steyn registreert zich op `/registreren` met het adres uit `ADMIN_EMAILS`, koppelt de authenticator-app en **bewaart de herstelcodes** op een veilige plek. Daarna komt hij in het beheer.
- [ ] **Website-teksten**: alle pagina's nalopen. Bevestig vooral de prijzen; die van online coaching zijn nog een voorstel.
- [ ] **Agenda → Instellingen**: beschikbaarheid, vrije dagen en eventueel de koppeling met Google of Apple Agenda.
- [ ] Met een tweede e-mailadres als klant registreren, een afspraak boeken, de intake invullen en het contactformulier versturen. De aanvraag moet in het beheer verschijnen.
- [ ] Een schema laten maken met AI. Dat duurt één à twee minuten.
- [ ] De site op een telefoon bekijken.
- [ ] Testaccounts daarna verwijderen. Dat kan het lid zelf, via Profiel → Account verwijderen.

Links in uitnodigingen verwijzen nu al naar www.steynpt.nl, waar nog de oude site staat. Dat is normaal tijdens het testen.

## Stap 8. Overstappen: www.steynpt.nl naar de nieuwe site

**Vooraf**

- [ ] **Maak een back-up van de oude WordPress-site**: DirectAdmin → Back-up maken. Noteer wat je nog van de oude site wilt bewaren.
- [ ] **Zoek uit waar de e-mail van steynpt.nl draait.** Staat de mailbox bij Vimexx, laat dan de **MX-records** en het webhostingpakket ongemoeid. Je past alleen de records voor de website aan.
- [ ] Zet de TTL van de records hieronder een dag vooraf laag, bijvoorbeeld 300 seconden, als dat kan. Dan gaat de overstap sneller.

**Overstappen**

1. Zet de definitieve Caddy-instellingen neer: `www.steynpt.nl`, met `steynpt.nl` doorgestuurd naar www.

   ```bash
   cp /srv/steynpt/current/deploy/Caddyfile /etc/caddy/Caddyfile
   systemctl reload caddy
   ```
2. Pas de DNS aan:

   | Naam | Type | Inhoud |
   | --- | --- | --- |
   | `@` (steynpt.nl) | A | `123.45.67.89` |
   | `www` | A | `123.45.67.89` |

   Staan er **AAAA-records** (IPv6) die naar de oude hosting wijzen, verwijder die dan. Anders komen sommige bezoekers nog op de oude site uit en lukt het certificaat niet.
3. Wacht tot de DNS is bijgewerkt: meestal minuten, soms een paar uur. Caddy haalt dan zelf het certificaat op.

**Controleren**

- [ ] https://www.steynpt.nl toont de nieuwe site.
- [ ] https://steynpt.nl stuurt door naar www.
- [ ] Oude adressen zoals `/over-steynpt` en `/vraag-een-gratis-kennismaking-aan` sturen door naar de nieuwe pagina's.
- [ ] Inloggen en het beheer werken.
- [ ] Meld de sitemap aan in Google Search Console: `https://www.steynpt.nl/sitemap.xml`.

Houd het oude webhostingpakket nog een paar weken aan, voor het geval je iets terug wilt zoeken. Zeg het daarna alleen op als de e-mail er niet op draait.

## Stap 9. Back-ups

Bij elke update maakt het deploy-script al een back-up van de database. Voeg daar een dagelijkse back-up aan toe:

```bash
sudo -iu steynpt
crontab -e
```

Zet onderaan deze regel. Elke nacht om 3:15 komt er een back-up bij, en back-ups ouder dan 30 dagen gaan weg:

```
15 3 * * * cd /srv/steynpt/current && node --env-file=.env scripts/backup-db.mjs /srv/steynpt/backups >> /srv/steynpt/backups/backup.log 2>&1
```

**Ook buiten de server bewaren.** Download regelmatig een kopie naar je eigen computer of cloudopslag:

```bash
scp root@123.45.67.89:/srv/steynpt/backups/steynpt-*.db ~/steynpt-backups/
```

Biedt Vimexx snapshots of back-ups van de hele VPS aan, zet die dan ook aan.

**Een back-up terugzetten**

```bash
systemctl stop steynpt
cp /srv/steynpt/backups/steynpt-2026-10-05-03-15-00.db /srv/steynpt/data/steynpt.db
chown steynpt:steynpt /srv/steynpt/data/steynpt.db
systemctl start steynpt
```

## Stap 10. Updates live zetten

Is er een nieuwe versie op `main`? Dan zet één commando hem live:

```bash
ssh root@123.45.67.89
sudo -iu steynpt
bash /srv/steynpt/current/deploy/deploy.sh
```

Het script werkt zo:

- Het bouwt de nieuwe versie in een eigen map, terwijl de huidige site gewoon online blijft.
- Vóór het bijwerken van de database maakt het een back-up.
- Pas als alles gelukt is, schakelt het om. De site is dan een paar seconden weg.
- Start de nieuwe versie niet goed op, dan zet het script automatisch de vorige versie terug.
- Mislukt het bouwen, dan blijft de huidige site gewoon draaien.
- De laatste drie versies blijven bewaard.

**Zelf een versie terugzetten**

```bash
ls /srv/steynpt/releases                                    # de bewaarde versies
ln -sfn /srv/steynpt/releases/20261005-112147 /srv/steynpt/current
sudo systemctl restart steynpt
```

## Problemen oplossen

| Wat zie je | Wat te doen |
| --- | --- |
| *502 Bad Gateway* | De site draait niet. Bekijk `systemctl status steynpt` en `journalctl -u steynpt -n 50`. |
| Geen certificaat, of een waarschuwing in de browser | De DNS wijst nog niet naar de VPS, of een oud AAAA-record wijst ergens anders heen. Controleer ook dat poort 80 en 443 open staan (`ufw status`). Bekijk `journalctl -u caddy -n 50`. |
| Bouwen stopt met *out of memory* of *Killed* | Te weinig geheugen. Voeg swap toe (stap 3) of kies Entry M. |
| Steyn kan niet inloggen: telefoon kwijt en geen herstelcodes | `sudo -iu steynpt`, dan `cd /srv/steynpt/current && npm run auth:reset-2fa -- steyn@voorbeeld.nl`. Bij de volgende keer inloggen koppelt hij de app opnieuw. |
| AI-schema's werken niet | Controleer `ANTHROPIC_API_KEY` in `/srv/steynpt/shared/.env` en het tegoed op platform.claude.com. Herstart daarna met `systemctl restart steynpt`. |
| De logboeken bekijken | Site: `journalctl -u steynpt -f`. Webserver: `journalctl -u caddy -f`. |

## Bestanden in de repository

| Bestand | Waarvoor |
| --- | --- |
| `deploy/deploy.sh` | Een nieuwe versie live zetten (stap 6 en 10) |
| `deploy/steynpt.service` | De site als service: start automatisch en herstart na een fout |
| `deploy/Caddyfile` | HTTPS en doorsturen naar de site, met steynpt.nl → www |
| `scripts/backup-db.mjs` | Back-up van de database (`npm run db:backup`) |
