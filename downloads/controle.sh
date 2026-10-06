#!/usr/bin/env bash
# Controle: hoe ver ben je met het online zetten van de nieuwe SteynPT-website?
# Kijkt op de hosting per deel van de afvinklijst (docs/online-zetten) wat al klaar is en zegt waar je verder moet.
# Verandert niets. Gebruik, na inloggen via SSH:
#   curl -fsSL https://github.com/StephvanHoffe/steynpt/raw/claude/dreamy-brown-cauqyb/downloads/controle.sh | bash

main() {
  local DOM="$HOME/domains/steynpt.nl"
  local APP="$DOM/steynpt"
  local PHP=/opt/alt/php83/usr/bin/php
  [ -x "$PHP" ] || PHP=php

  local G=$'\e[32m' R=$'\e[31m' Y=$'\e[33m' D=$'\e[2m' B=$'\e[1m' N=$'\e[0m'
  NEXT=""
  ok()   { printf '  %s✓%s %s\n' "$G" "$N" "$1"; }
  fout() { printf '  %s✗%s %s\n' "$R" "$N" "$1"; [ -n "${2:-}" ] && printf '      %s→ %s%s\n' "$Y" "$2" "$N"; [ -z "$NEXT" ] && NEXT="${3:-}"; }
  vraag() { printf '  %s?%s %s\n' "$Y" "$N" "$1"; }
  later() { printf '  %s·  %s%s\n' "$D" "$1" "$N"; }
  kop()  { printf '\n%s%s%s\n' "$B" "$1" "$N"; }

  printf '\n%sControle: hoe ver ben je?%s\n' "$B" "$N"

  # Deel 5: verbonden met de hosting
  kop "Deel 5 · Verbinden"
  if [ ! -d "$HOME/domains" ]; then
    fout "Je bent niet verbonden met de hosting." "Doe eerst stap 5.3 en 5.5 en plak deze controle daarna opnieuw." "deel 5, stap 5.3"
    printf '\n%sJe bent gebleven bij: %s%s\n\n' "$B" "$NEXT" "$N"
    return
  fi
  ok "Je bent verbonden met de hosting ($(whoami))."
  if "$PHP" -v 2>/dev/null | head -1 | grep -q "PHP 8\.3"; then
    ok "PHP 8.3 is er."
  else
    fout "PHP 8.3 is niet gevonden." "Vraag de klantenservice van Vimexx of PHP 8.3 op je pakket kan." "deel 5, stap 5.8"
  fi
  if grep -qs "alias php=/opt/alt/php83" "$HOME/.bashrc"; then
    ok "De terminal gebruikt PHP 8.3 (stap 5.7)."
  else
    fout "Stap 5.7 is nog niet gedaan." "Plak de drie regels van stap 5.7." "deel 5, stap 5.7"
  fi

  # Deel 6 en 7: pakket ophalen en uitpakken
  kop "Deel 6 en 7 · Het pakket"
  if [ -f "$APP/artisan" ] && [ -d "$APP/vendor" ]; then
    ok "Het pakket is opgehaald en uitgepakt (map steynpt)."
  elif [ -f "$DOM/steynpt-php-website.zip" ]; then
    if unzip -tq "$DOM/steynpt-php-website.zip" >/dev/null 2>&1; then
      ok "Het pakket is opgehaald (deel 6)."
      fout "Het pakket is nog niet uitgepakt." "Plak de regels van stap 7.1." "deel 7, stap 7.1"
    else
      fout "Het pakket is niet helemaal opgehaald." "Doe deel 6 nog een keer." "deel 6, stap 6.1"
    fi
  else
    fout "Het pakket is nog niet opgehaald." "Plak de regels van stap 6.1." "deel 6, stap 6.1"
  fi
  if [ ! -f "$APP/artisan" ]; then
    later "Deel 8 en verder: daar ben je nog niet aan toe."
    printf '\n%sJe bent gebleven bij: %s%s\n\n' "$B" "$NEXT" "$N"
    return
  fi

  # Deel 8: website instellen
  kop "Deel 8 · Instellen"
  local env="$APP/.env" dbok=0
  if [ ! -f "$env" ]; then
    fout "De website is nog niet ingesteld." "Plak de regels van stap 8.2 en beantwoord de vragen." "deel 8, stap 8.2"
  else
    if grep -qE '^APP_KEY=.+' "$env" && grep -qE '^DB_DATABASE=.+' "$env" && grep -qE '^DB_USERNAME=.+' "$env"; then
      ok "De instellingen zijn ingevuld."
    else
      fout "De instellingen zijn niet compleet." "Doe deel 8 nog een keer, vanaf stap 8.2." "deel 8, stap 8.2"
    fi
    local status
    status=$(cd "$APP" && "$PHP" artisan migrate:status --no-ansi --no-interaction 2>&1)
    if [ $? -eq 0 ] && ! printf '%s' "$status" | grep -q "Pending"; then
      ok "De database werkt en alle tabellen staan klaar."
      dbok=1
    elif printf '%s' "$status" | grep -qi "access denied\|unknown database\|connection refused\|SQLSTATE"; then
      fout "De website kan niet bij de database." "Doe deel 8 nog een keer en vul de naam, gebruiker en het wachtwoord uit deel 4 precies over." "deel 8, stap 8.2"
    else
      fout "De tabellen zijn nog niet (allemaal) gemaakt." "Doe deel 8 nog een keer, vanaf stap 8.2." "deel 8, stap 8.2"
    fi
    if [ -f "$APP/bootstrap/cache/config.php" ]; then
      ok "De website is klaargezet (optimize)."
    else
      fout "Het laatste stukje van deel 8 ontbreekt." "Plak: cd ~/domains/steynpt.nl/steynpt && php artisan optimize" "deel 8, stap 8.10"
    fi
  fi

  # Deel 9: wekker (cronjob) en back-up
  kop "Deel 9 · De wekker"
  local cron
  if cron=$(crontab -l 2>/dev/null); then
    if printf '%s' "$cron" | grep -q "$APP.*schedule:run"; then
      ok "De wekker (cronjob) staat goed."
    elif printf '%s' "$cron" | grep -q "schedule:run"; then
      fout "Er staat een wekker, maar met een verkeerde map." "Vergelijk hem in DirectAdmin bij Cronjobs met de regel van stap 9.3 (met jouw gebruikersnaam: $(whoami))." "deel 9, stap 9.3"
    else
      fout "De wekker (cronjob) staat er nog niet." "Doe deel 9." "deel 9, stap 9.1"
    fi
  else
    vraag "De wekker kan ik van hieruit niet zien. Kijk in DirectAdmin bij Cronjobs of de regel van stap 9.3 erin staat."
  fi
  if ls "$APP"/storage/backups/steynpt-* >/dev/null 2>&1; then
    ok "Er is een reservekopie van de database (stap 9.6)."
  else
    fout "Stap 9.6 (back-up testen) is nog niet gedaan." "Plak de regels van stap 9.6." "deel 9, stap 9.6"
  fi

  # Deel 10: reservekopie van de oude site
  kop "Deel 10 · Reservekopie van de oude site"
  if ls "$HOME"/backups/*.tar* >/dev/null 2>&1 || ls "$HOME"/user_backups/*.tar* >/dev/null 2>&1; then
    ok "Er staat een back-up van DirectAdmin."
  else
    vraag "Geen back-up van DirectAdmin gevonden. Heb je deel 10 gedaan en kreeg je het bericht dat hij klaar was? Dan is het goed."
  fi

  # Deel 11: overstappen
  kop "Deel 11 · Overstappen"
  if grep -qs "x-lsphp83" "$DOM/.htaccess"; then
    ok "PHP 8.3 staat aan voor steynpt.nl (stap 11.2)."
  else
    fout "PHP 8.3 staat nog niet aan voor steynpt.nl." "Plak de regels van stap 11.2." "deel 11, stap 11.2"
  fi
  if [ -L "$DOM/public_html" ]; then
    if [ "$(readlink "$DOM/public_html")" = "steynpt/public" ]; then
      ok "De nieuwe site staat aan (stap 11.4). De oude staat bewaard als public_html-oud."
    else
      fout "public_html wijst naar de verkeerde plek." "Zet de oude site terug (Hulp in de afvinklijst) en doe stap 11.4 opnieuw." "deel 11, stap 11.4"
    fi
  else
    fout "De oude site staat nog aan." "Plak de regels van stap 11.4 (op een rustig moment)." "deel 11, stap 11.4"
  fi

  # Deel 12 en 13: bereikbaar en slotje
  kop "Deel 12 en 13 · Bereikbaar en het slotje"
  local code body redirect
  code=$(curl -s -o /dev/null -w '%{http_code}' --max-time 15 https://www.steynpt.nl/ 2>/dev/null)
  body=$(curl -s --max-time 15 https://www.steynpt.nl/ 2>/dev/null)
  if [ "$code" = "200" ] && printf '%s' "$body" | grep -q "/build/assets/"; then
    ok "https://www.steynpt.nl laat de nieuwe site zien, met slotje."
  elif [ "$code" = "200" ]; then
    fout "https://www.steynpt.nl laat nog de oude site zien." "Heb je deel 11 gedaan? Dan is deel 12 (het domein) misschien nodig." "deel 12"
  elif [ "$code" = "000" ]; then
    fout "https://www.steynpt.nl werkt nog niet (geen slotje of niet bereikbaar)." "Doe deel 13." "deel 13, stap 13.1"
  else
    fout "https://www.steynpt.nl geeft een foutmelding (code $code)." "Kijk bij Hulp in de afvinklijst." "Hulp"
  fi
  redirect=$(curl -s -o /dev/null -w '%{http_code} %{redirect_url}' --max-time 15 http://www.steynpt.nl/ 2>/dev/null)
  case "$redirect" in
    30[12378]\ https://*) ok "Wie http typt, komt vanzelf op https (stap 13.4)." ;;
    *) fout "Het vinkje 'Forceer SSL' staat nog niet aan." "Doe stap 13.3 en 13.4." "deel 13, stap 13.3" ;;
  esac

  # Deel 15: account van Steyn
  kop "Deel 15 · Het account van Steyn"
  if [ "$dbok" = 1 ]; then
    local admins
    admins=$(cd "$APP" && "$PHP" -r 'require "vendor/autoload.php"; $app = require "bootstrap/app.php"; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); echo App\Models\User::query()->where("role", "admin")->count();' 2>/dev/null)
    if [ "${admins:-0}" -gt 0 ] 2>/dev/null; then
      ok "Het account van Steyn bestaat en is beheerder."
    else
      fout "Het account van Steyn bestaat nog niet." "Doe deel 15." "deel 15, stap 15.1"
    fi
  else
    later "Kan ik pas zien als de database werkt (deel 8)."
  fi

  if [ -z "$NEXT" ]; then
    printf '\n%s%sAlles klaar! De nieuwe website staat online.%s\n\n' "$B" "$G" "$N"
  else
    printf '\n%sJe bent gebleven bij: %s%s\n' "$B" "$NEXT" "$N"
    printf 'Vink in de afvinklijst alles daarvóór af en ga daar verder. Hulp nodig? Maak een schermafbeelding van dit overzicht.\n\n'
  fi
}

main "$@" </dev/null
