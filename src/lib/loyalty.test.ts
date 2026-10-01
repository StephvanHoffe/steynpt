import assert from "node:assert/strict";
import { test } from "node:test";
import {
  checkInStreak,
  getTierProgress,
  isoWeekKey,
  makeReferralCode,
  normalizeReferralCode,
  POINTS,
  welcomePoints,
} from "./loyalty";

test("isoWeekKey volgt ISO-8601", () => {
  assert.equal(isoWeekKey(new Date(2026, 9, 1)), "2026-W40");
  assert.equal(isoWeekKey(new Date(2027, 0, 1)), "2026-W53");
  assert.equal(isoWeekKey(new Date(2025, 11, 29)), "2026-W01");
});

test("streak telt aaneengesloten weken", () => {
  const now = new Date(2026, 9, 1);
  assert.equal(checkInStreak([], now), 0);
  assert.equal(checkInStreak(["2026-W40", "2026-W39", "2026-W38"], now), 3);
  // Deze week nog niet ingecheckt: streak loopt door vanaf vorige week.
  assert.equal(checkInStreak(["2026-W39", "2026-W38"], now), 2);
  assert.equal(checkInStreak(["2026-W40", "2026-W38"], now), 1);
});

test("niveaus op basis van totaal verdiende punten", () => {
  assert.equal(getTierProgress(0).current.id, "brons");
  assert.equal(getTierProgress(499).next?.id, "zilver");
  assert.equal(getTierProgress(500).current.id, "zilver");
  const top = getTierProgress(10_000);
  assert.equal(top.current.id, "platina");
  assert.equal(top.next, null);
  assert.equal(top.progress, 1);
});

test("welkomstpunten verdubbelen tijdens de lanceringsactie", () => {
  assert.equal(welcomePoints(new Date("2026-10-15T12:00:00Z")), POINTS.welcome * 2);
  assert.equal(welcomePoints(new Date("2027-02-01T12:00:00Z")), POINTS.welcome);
});

test("uitnodigingscodes", () => {
  const code = makeReferralCode("Zoë-Anne", () => 0);
  assert.equal(code, "ZOEANNE-AAAA");
  assert.equal(normalizeReferralCode(" zoeanne-aaaa "), "ZOEANNE-AAAA");
  assert.equal(normalizeReferralCode("bad code"), null);
  assert.equal(makeReferralCode("", () => 0), "STEYN-AAAA");
});
