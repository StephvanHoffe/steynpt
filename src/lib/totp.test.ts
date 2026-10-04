import assert from "node:assert/strict";
import test from "node:test";
import {
  base32Decode,
  base32Encode,
  generateRecoveryCodes,
  generateTotpSecret,
  hashRecoveryCode,
  hotp,
  isRecoveryCodeFormat,
  otpauthUri,
  passwordDaysLeft,
  totp,
  verifyTotp,
} from "./totp";

// Geheim uit de testvectoren van RFC 6238/4226: "12345678901234567890".
const RFC_SECRET = base32Encode(Buffer.from("12345678901234567890"));

test("totp: base32 heen en terug", () => {
  assert.equal(RFC_SECRET, "GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ");
  assert.equal(base32Decode(RFC_SECRET).toString(), "12345678901234567890");
  assert.equal(base32Decode("gezd gnbv gy3t qojq gezd gnbv gy3t qojq").toString(), "12345678901234567890", "spaties en kleine letters mogen");
  const secret = generateTotpSecret();
  assert.equal(secret.length, 32);
  assert.equal(base32Decode(secret).length, 20);
});

test("totp: testvectoren uit de RFC", () => {
  assert.equal(hotp(RFC_SECRET, 0), "755224");
  assert.equal(hotp(RFC_SECRET, 9), "520489");
  assert.equal(totp(RFC_SECRET, 59_000), "287082");
  assert.equal(totp(RFC_SECRET, 1111111109_000), "081804");
  assert.equal(totp(RFC_SECRET, 2000000000_000), "279037");
});

test("totp: controleren met speling en zonder hergebruik", () => {
  const now = 1111111109_000;
  const step = Math.floor(now / 30_000);
  assert.equal(verifyTotp(RFC_SECRET, "081804", { now }), step);
  assert.equal(verifyTotp(RFC_SECRET, "081 804", { now }), step, "spatie mag");
  assert.equal(verifyTotp(RFC_SECRET, totp(RFC_SECRET, now - 30_000), { now }), step - 1, "vorige code nog geldig");
  assert.equal(verifyTotp(RFC_SECRET, totp(RFC_SECRET, now + 30_000), { now }), step + 1, "klok iets achter");
  assert.equal(verifyTotp(RFC_SECRET, totp(RFC_SECRET, now - 90_000), { now }), null, "te oud");
  assert.equal(verifyTotp(RFC_SECRET, "081804", { now, lastStep: step }), null, "al gebruikt");
  assert.equal(verifyTotp(RFC_SECRET, "12345", { now }), null);
  assert.equal(verifyTotp(RFC_SECRET, "abcdef", { now }), null);
});

test("totp: herstelcodes, QR-link en wachtwoordtermijn", () => {
  const codes = generateRecoveryCodes();
  assert.equal(codes.length, 8);
  assert.equal(new Set(codes).size, 8);
  assert.match(codes[0], /^[a-z2-9]{5}-[a-z2-9]{5}$/);
  assert.ok(isRecoveryCodeFormat(codes[0].toUpperCase().replace("-", " ")));
  assert.equal(hashRecoveryCode(codes[0]), hashRecoveryCode(codes[0].toUpperCase().replace("-", "")));
  assert.ok(!isRecoveryCodeFormat("123456"));

  assert.equal(
    otpauthUri("ABC", "lisa@example.com"),
    "otpauth://totp/SteynPT%3Alisa%40example.com?secret=ABC&issuer=SteynPT&algorithm=SHA1&digits=6&period=30",
  );

  const changed = new Date("2026-08-01T10:00:00Z");
  assert.equal(passwordDaysLeft(changed, new Date("2026-08-01T10:00:00Z")), 56);
  assert.equal(passwordDaysLeft(changed, new Date("2026-09-25T11:00:00Z")), 1, "nog 23 uur");
  assert.equal(passwordDaysLeft(changed, new Date("2026-09-26T10:00:00Z")), 0, "precies 8 weken: verlopen");
});
