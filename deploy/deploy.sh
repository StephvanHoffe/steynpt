#!/usr/bin/env bash
# Zet de nieuwste versie van de site live op de VPS. Zie docs/live-zetten-vimexx.md.
#
# Haalt de code op in een nieuwe map (releases/<datum-tijd>), installeert, maakt een back-up van de database,
# werkt de database bij en bouwt. Pas als dat allemaal gelukt is, schakelt de site om naar de nieuwe versie.
# De oude versie blijft tot dat moment gewoon online. Start de nieuwe versie niet goed op, dan gaat automatisch
# de vorige versie terug.
#
# Draaien als de gebruiker 'steynpt':  bash /srv/steynpt/deploy.sh
set -euo pipefail

APP_DIR="${APP_DIR:-/srv/steynpt}"
REPO="${REPO:-git@github.com:StephvanHoffe/steynpt.git}"
BRANCH="${BRANCH:-main}"
SERVICE="${SERVICE:-steynpt}"
PORT="${PORT:-3000}"
KEEP="${KEEP:-3}" # zoveel versies blijven bewaard, voor als je terug wilt

release="$APP_DIR/releases/$(date +%Y%m%d-%H%M%S)"
previous="$(readlink -f "$APP_DIR/current" 2>/dev/null || true)"

restart() {
  sudo /usr/bin/systemctl restart "$SERVICE"
}

healthy() {
  for _ in $(seq 1 60); do
    curl -fs -o /dev/null "http://127.0.0.1:$PORT/" && return 0
    sleep 1
  done
  return 1
}

# Mislukt er iets vóór het omschakelen, dan gaat de half afgemaakte map weer weg. De site merkt er niets van.
switched=0
trap '[ "$switched" = 1 ] || rm -rf -- "${release:?}"' EXIT

echo "→ Code ophalen (branch $BRANCH)"
mkdir -p "$APP_DIR/releases"
git clone --quiet --depth 1 --branch "$BRANCH" "$REPO" "$release"
ln -s "$APP_DIR/shared/.env" "$release/.env"
cd "$release"
echo "  versie: $(git log -1 --format='%h %s')"

echo "→ Pakketten installeren"
npm ci --no-audit --no-fund --loglevel=error

echo "→ Back-up van de database"
node --env-file=.env scripts/backup-db.mjs "$APP_DIR/backups"

echo "→ Database bijwerken"
node --env-file=.env scripts/migrate.mjs

echo "→ Bouwen (duurt een paar minuten)"
npm run build

echo "→ Omschakelen naar de nieuwe versie"
ln -sfn "$release" "$APP_DIR/current"
switched=1
restart

if ! healthy; then
  echo "✗ De nieuwe versie reageert niet. Bekijk de foutmelding met: journalctl -u $SERVICE -n 50" >&2
  if [ -n "$previous" ] && [ -d "$previous" ]; then
    echo "  Terug naar de vorige versie: $(basename "$previous")" >&2
    ln -sfn "$previous" "$APP_DIR/current"
    restart
    cd "$APP_DIR"
    rm -rf -- "${release:?}"
  fi
  exit 1
fi

# Oude versies opruimen (de nieuwste $KEEP blijven staan).
find "$APP_DIR/releases" -mindepth 1 -maxdepth 1 -type d -printf '%f\n' | sort -r | tail -n +"$((KEEP + 1))" | while read -r old; do
  rm -rf -- "${APP_DIR:?}/releases/${old:?}"
done

echo "✓ Live: $(basename "$release")"
