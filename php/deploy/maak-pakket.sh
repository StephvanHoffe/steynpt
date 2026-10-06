#!/usr/bin/env bash
# Maakt een installatiepakket (zip) van de PHP-versie voor gedeelde hosting zoals Vimexx Webhosting.
# Het pakket bevat alles wat de server nodig heeft: de code, vendor/ (zonder ontwikkelpakketten) en de
# gebouwde CSS/JS in public/build. Op de server is dus alleen PHP nodig (geen Composer of Node.js).
#
# Gebruik (op je eigen computer, in de map php/):  bash deploy/maak-pakket.sh
# Resultaat: dist/steynpt-php-<datum>.zip met daarin de map steynpt/
set -euo pipefail

cd "$(dirname "$0")/.."
ROOT="$(pwd)"
STAMP="$(date +%Y%m%d-%H%M)"
WORK="$(mktemp -d)"
OUT="$ROOT/dist/steynpt-php-$STAMP.zip"
trap 'rm -rf "$WORK"' EXIT

echo "1/4 CSS en JavaScript bouwen…"
npm ci --no-audit --no-fund >/dev/null
npm run build >/dev/null

echo "2/4 Bestanden verzamelen…"
mkdir -p "$WORK/steynpt"
tar -C "$ROOT" -cf - \
  --exclude=./.git --exclude=./node_modules --exclude=./vendor --exclude=./dist --exclude=./tests --exclude=./.env \
  --exclude=./.phpunit.cache --exclude=./public/hot --exclude=./public/storage \
  --exclude='./storage/logs/*.log' --exclude='./storage/backups/steynpt-*' --exclude='./storage/framework/sessions/*' --exclude='./storage/framework/views/*.php' \
  --exclude='./storage/framework/cache/data/*' --exclude='./bootstrap/cache/*.php' --exclude='./database/*.sqlite*' \
  . | tar -C "$WORK/steynpt" -xf -

echo "3/4 PHP-pakketten installeren (zonder ontwikkelpakketten)…"
(cd "$WORK/steynpt" && composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-progress --quiet)
# Geen git-geschiedenis of testbestanden van pakketten meesturen.
find "$WORK/steynpt" -name .git -type d -prune -exec rm -rf {} +
# Ook geen testsuites, documentatie, voorbeeldplaatjes en hulpprogramma's van pakketten (alleen wat de site gebruikt),
# zodat het pakket klein genoeg blijft om vanaf de handleiding te downloaden.
find "$WORK/steynpt/vendor" -mindepth 3 -maxdepth 3 \( -type d \( -name tests -o -name Tests -o -name test -o -name docs -o -name doc -o -name art -o -name .github \) -o -type f -name '*.png' \) -prune -exec rm -rf {} +
rm -rf "$WORK/steynpt/vendor/laravel/framework/bin"
(cd "$WORK/steynpt" && composer dump-autoload --no-dev --optimize --no-interaction --quiet)

echo "4/4 Zip maken…"
mkdir -p "$ROOT/dist"
(cd "$WORK" && zip -qr -9 "$OUT" steynpt)
# De nieuwste versie ook onder een vaste naam in downloads/ (in git), zodat de downloadknop in de handleiding
# (docs/online-zetten) altijd naar het nieuwste pakket wijst.
mkdir -p "$ROOT/../downloads" && cp "$OUT" "$ROOT/../downloads/steynpt-php-website.zip"
echo "Klaar: $OUT ($(du -h "$OUT" | cut -f1))"
