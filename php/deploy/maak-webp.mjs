// Maakt lichtere WebP-versies van de foto's in public/images (voor snellere pagina's, telt mee voor Google).
// Per foto: naam.480w.webp, naam.800w.webp en naam.<breedte>w.webp (nooit breder dan het origineel).
// Draaien vanuit de hoofdmap van het project (gebruikt sharp uit node_modules): node php/deploy/maak-webp.mjs
// Het onderdeel <x-photo> pakt de versies die er zijn vanzelf op; zonder versies toont het gewoon de jpg.
import { readdirSync, statSync } from "node:fs";
import { join } from "node:path";
import sharp from "sharp";

const dir = new URL("../public/images/", import.meta.url).pathname;
const SKIP = new Set(["og-steynpt.jpg"]); // deelafbeelding voor social media: blijft jpg
for (const file of readdirSync(dir)) {
  if (!/\.(jpe?g|png)$/i.test(file) || SKIP.has(file)) continue;
  const src = join(dir, file);
  const { width } = await sharp(src).metadata();
  const widths = [...new Set([480, 800, width].map((w) => Math.min(w, width)))].sort((a, b) => a - b);
  for (const w of widths) {
    const out = join(dir, file.replace(/\.(jpe?g|png)$/i, `.${w}w.webp`));
    await sharp(src).resize({ width: w }).webp({ quality: 78 }).toFile(out);
    console.log(`${file} → ${out.split("/").pop()} (${Math.round(statSync(out).size / 1024)} kB, origineel ${Math.round(statSync(src).size / 1024)} kB)`);
  }
}
