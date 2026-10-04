// Tweestapsverificatie met een authenticator-app (TOTP, RFC 6238): 6 cijfers, elke 30 seconden een nieuwe code.
// Puur (geen database), zodat het los te testen is.

import { createHash, createHmac, randomBytes, timingSafeEqual } from "node:crypto";

export const TOTP_PERIOD = 30;
const DIGITS = 6;
const ALPHABET = "ABCDEFGHIJKLMNOPQRSTUVWXYZ234567";

export function base32Encode(bytes: Uint8Array) {
  let bits = 0;
  let value = 0;
  let out = "";
  for (const byte of bytes) {
    value = (value << 8) | byte;
    bits += 8;
    while (bits >= 5) {
      out += ALPHABET[(value >>> (bits - 5)) & 31];
      bits -= 5;
    }
  }
  if (bits > 0) out += ALPHABET[(value << (5 - bits)) & 31];
  return out;
}

export function base32Decode(input: string) {
  const clean = input.toUpperCase().replace(/[\s=-]/g, "");
  let bits = 0;
  let value = 0;
  const out: number[] = [];
  for (const char of clean) {
    const index = ALPHABET.indexOf(char);
    if (index < 0) throw new Error("Ongeldige base32-tekst");
    value = (value << 5) | index;
    bits += 5;
    if (bits >= 8) {
      out.push((value >>> (bits - 8)) & 255);
      bits -= 8;
    }
  }
  return Buffer.from(out);
}

/** Nieuw geheim van 160 bits, zoals authenticator-apps verwachten. */
export const generateTotpSecret = () => base32Encode(randomBytes(20));

/** Geheim in groepjes van vier, makkelijker over te typen. */
export const formatSecret = (secret: string) => secret.replace(/(.{4})/g, "$1 ").trim();

export function hotp(secret: string, counter: number, digits = DIGITS) {
  const buf = Buffer.alloc(8);
  buf.writeBigUInt64BE(BigInt(counter));
  const hmac = createHmac("sha1", base32Decode(secret)).update(buf).digest();
  const offset = hmac[hmac.length - 1] & 15;
  const binary = (hmac.readUInt32BE(offset) & 0x7fffffff) % 10 ** digits;
  return String(binary).padStart(digits, "0");
}

export const totpStep = (now = Date.now()) => Math.floor(now / 1000 / TOTP_PERIOD);
export const totp = (secret: string, now = Date.now()) => hotp(secret, totpStep(now));

/**
 * Controleert een code met een halve minuut speling naar voren en achteren (klokverschil).
 * Een code die al eens is gebruikt (stap <= lastStep) wordt geweigerd. Geeft de gebruikte stap terug, of null.
 */
export function verifyTotp(secret: string, code: string, { now = Date.now(), lastStep = null as number | null } = {}) {
  const clean = code.replace(/\s/g, "");
  if (!/^\d{6}$/.test(clean)) return null;
  const current = totpStep(now);
  for (const step of [current, current - 1, current + 1]) {
    if (lastStep !== null && step <= lastStep) continue;
    const expected = hotp(secret, step);
    if (timingSafeEqual(Buffer.from(expected), Buffer.from(clean))) return step;
  }
  return null;
}

/** Link voor de QR-code: de app toont "SteynPT" met het e-mailadres. */
export function otpauthUri(secret: string, account: string, issuer = "SteynPT") {
  const label = encodeURIComponent(`${issuer}:${account}`);
  return `otpauth://totp/${label}?secret=${secret}&issuer=${encodeURIComponent(issuer)}&algorithm=SHA1&digits=${DIGITS}&period=${TOTP_PERIOD}`;
}

// Herstelcodes: eenmalig te gebruiken als de telefoon met de app kwijt is.
const RECOVERY_ALPHABET = "abcdefghjkmnpqrstuvwxyz23456789";

export function generateRecoveryCodes(count = 8) {
  return Array.from({ length: count }, () => {
    const bytes = randomBytes(10);
    const chars = Array.from(bytes, (b) => RECOVERY_ALPHABET[b % RECOVERY_ALPHABET.length]).join("");
    return `${chars.slice(0, 5)}-${chars.slice(5)}`;
  });
}

export const normalizeRecoveryCode = (code: string) => code.toLowerCase().replace(/[^a-z0-9]/g, "");
export const isRecoveryCodeFormat = (code: string) => normalizeRecoveryCode(code).length === 10;
export const hashRecoveryCode = (code: string) => createHash("sha256").update(normalizeRecoveryCode(code)).digest("hex");

// Wachtwoordbeleid: om de 8 weken een nieuw wachtwoord.
export const PASSWORD_MAX_AGE_DAYS = 56;
const DAY = 24 * 60 * 60 * 1000;

export function passwordExpiresAt(changedAt: Date) {
  return new Date(changedAt.getTime() + PASSWORD_MAX_AGE_DAYS * DAY);
}

/** Dagen tot het wachtwoord verloopt (0 of minder = verlopen). */
export function passwordDaysLeft(changedAt: Date, now = new Date()) {
  return Math.ceil((passwordExpiresAt(changedAt).getTime() - now.getTime()) / DAY);
}
