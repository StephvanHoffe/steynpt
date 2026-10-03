import assert from "node:assert/strict";
import test from "node:test";
import { compareByUrgency, countGroups, defaultRenewOn, inPipeline, planStage, relativeDay, type PipelineInput } from "./pipeline";

const today = "2026-10-03";
const base: PipelineInput = { type: "training", coachingStatus: "actief", wants: ["training", "voeding"], current: null, open: null };
const published = (publishedDay: string, renewOn: string | null = null, durationWeeks: number | null = null) => ({ publishedDay, renewOn, durationWeeks });

test("schema-planning: looptijd", () => {
  assert.equal(defaultRenewOn("training", "2026-10-01", 8), "2026-11-26", "trainingsschema volgt de duur uit het schema");
  assert.equal(defaultRenewOn("training", "2026-10-01", null), "2026-11-12", "zonder duur 6 weken");
  assert.equal(defaultRenewOn("training", "2026-10-01", 99), "2026-11-12", "onzinnige duur wordt genegeerd");
  assert.equal(defaultRenewOn("voeding", "2026-10-01", 8), "2026-10-29", "voeding altijd 4 weken");
});

test("schema-planning: wie staat in het overzicht", () => {
  assert.equal(inPipeline(base), true);
  assert.equal(inPipeline({ ...base, coachingStatus: "geen" }), false, "geen coaching en geen schema");
  assert.equal(inPipeline({ ...base, coachingStatus: "geen", current: published("2026-09-01") }), true, "wel als er al een schema is");
  assert.equal(inPipeline({ ...base, wants: ["voeding"] }), false, "klant wil geen trainingsschema");
  assert.equal(inPipeline({ ...base, wants: null }), true, "zonder intake weten we het nog niet");
  assert.equal(inPipeline({ ...base, coachingStatus: "gestopt" }), false);
});

test("schema-planning: fases", () => {
  const stage = (input: Partial<PipelineInput>) => planStage({ ...base, ...input }, today);
  assert.deepEqual(stage({ wants: null }), { stage: "intake", dueOn: null });
  assert.deepEqual(stage({}), { stage: "eerste", dueOn: null });
  assert.equal(stage({ open: { status: "concept", stuck: false } }).stage, "controleren");
  assert.equal(stage({ open: { status: "genereren", stuck: false } }).stage, "bezig");
  assert.equal(stage({ open: { status: "genereren", stuck: true } }).stage, "mislukt");
  assert.equal(stage({ open: { status: "fout", stuck: false }, coachingStatus: "gepauzeerd" }).stage, "mislukt", "een open concept gaat voor");

  assert.deepEqual(stage({ current: published("2026-08-01", "2026-10-03") }), { stage: "verlopen", dueOn: "2026-10-03" }, "vandaag is de dag");
  assert.equal(stage({ current: published("2026-08-01", "2026-10-10") }).stage, "binnenkort");
  assert.equal(stage({ current: published("2026-08-01", "2026-10-11") }).stage, "actief");
  assert.deepEqual(stage({ current: published("2026-09-01", null, 6) }), { stage: "actief", dueOn: "2026-10-13" }, "zonder datum: publicatie + looptijd");
  assert.equal(stage({ current: published("2026-08-01", "2026-09-01"), coachingStatus: "gepauzeerd" }).stage, "pauze");
  assert.deepEqual(
    stage({ current: published("2026-08-01", "2026-09-30"), open: { status: "concept", stuck: false } }),
    { stage: "controleren", dueOn: "2026-09-30" },
    "nieuw concept voor een verlopen schema",
  );
});

test("schema-planning: volgorde, tellers en relatieve dagen", () => {
  const rows = [
    { name: "Actief", stage: "actief" as const, dueOn: "2026-11-01" },
    { name: "Bijna", stage: "binnenkort" as const, dueOn: "2026-10-08" },
    { name: "Laat", stage: "verlopen" as const, dueOn: "2026-09-20" },
    { name: "Nieuw", stage: "eerste" as const, dueOn: null },
    { name: "Concept", stage: "controleren" as const, dueOn: null },
  ];
  assert.deepEqual([...rows].sort(compareByUrgency).map((r) => r.name), ["Laat", "Nieuw", "Concept", "Bijna", "Actief"]);
  assert.deepEqual(countGroups(rows), { wacht: 2, controleren: 1, binnenkort: 1, actief: 1, intake: 0, pauze: 0 });

  assert.equal(relativeDay(today, today), "vandaag");
  assert.equal(relativeDay(today, "2026-10-04"), "morgen");
  assert.equal(relativeDay(today, "2026-10-08"), "over 5 dagen");
  assert.equal(relativeDay(today, "2026-09-30"), "3 dagen geleden");
  assert.equal(relativeDay(today, "2026-11-01"), "over 4 weken");
  assert.equal(relativeDay("2026-10-24", "2026-10-26"), "over 2 dagen", "over de wintertijd heen");
});
