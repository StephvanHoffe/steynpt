import assert from "node:assert/strict";
import { test } from "node:test";
import { makeReferralCode, normalizeReferralCode } from "./referral-program";
import { checkInStreak, isoWeekKey } from "./weeks";

test("isoWeekKey volgt ISO-8601", () => {
  assert.equal(isoWeekKey(new Date(2026, 9, 1)), "2026-W40");
  assert.equal(isoWeekKey(new Date(2027, 0, 1)), "2026-W53");
  assert.equal(isoWeekKey(new Date(2025, 11, 29)), "2026-W01");
});

test("streak telt aaneengesloten weken", () => {
  const now = new Date(2026, 9, 1);
  assert.equal(checkInStreak([], now), 0);
  assert.equal(checkInStreak(["2026-W40", "2026-W39", "2026-W38"], now), 3);
  assert.equal(checkInStreak(["2026-W39", "2026-W38"], now), 2);
  assert.equal(checkInStreak(["2026-W40", "2026-W38"], now), 1);
});

test("uitnodigingscodes", () => {
  assert.equal(makeReferralCode("Zoë-Anne", () => 0), "ZOEANNE-AAAA");
  assert.equal(normalizeReferralCode(" zoeanne-aaaa "), "ZOEANNE-AAAA");
  assert.equal(normalizeReferralCode("bad code"), null);
  assert.equal(makeReferralCode("", () => 0), "STEYN-AAAA");
});
