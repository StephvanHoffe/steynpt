// Back-up van de SQLite-database: een complete, consistente kopie (VACUUM INTO), ook terwijl de site draait.
// Gebruik: npm run db:backup -- [map]   (standaard ./backups). Oude back-ups ouder dan BACKUP_DAYS (30) dagen gaan weg.
import { existsSync, mkdirSync, readdirSync, rmSync, statSync } from "node:fs";
import { join, resolve } from "node:path";
import { createClient } from "@libsql/client";

const url = process.env.DATABASE_URL ?? "file:./data/steynpt.db";
if (!url.startsWith("file:")) {
  console.error("Dit script is voor een lokale SQLite-database (DATABASE_URL=file:…). Voor Turso: gebruik de back-ups van Turso.");
  process.exit(1);
}
const file = url.replace(/^file:(\/\/(?=\/))?/, "");
if (!existsSync(file)) {
  console.log(`Nog geen database op ${file}; er is niets om te back-uppen.`);
  process.exit(0);
}

const dir = resolve(process.argv[2] ?? process.env.BACKUP_DIR ?? "backups");
const keepDays = Number(process.env.BACKUP_DAYS ?? 30);
mkdirSync(dir, { recursive: true });

const stamp = new Date().toISOString().slice(0, 19).replace(/[:T]/g, "-");
let target = join(dir, `steynpt-${stamp}.db`);
for (let n = 2; existsSync(target); n++) target = join(dir, `steynpt-${stamp}-${n}.db`);
const client = createClient({ url });
await client.execute({ sql: "VACUUM INTO ?", args: [target] });
client.close();
console.log(`Back-up gemaakt: ${target}`);

const cutoff = Date.now() - keepDays * 86_400_000;
for (const name of readdirSync(dir)) {
  const path = join(dir, name);
  if (/^steynpt-[\d-]+\.db$/.test(name) && statSync(path).mtimeMs < cutoff) {
    rmSync(path);
    console.log(`Oude back-up verwijderd: ${name}`);
  }
}
