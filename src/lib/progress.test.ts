import assert from "node:assert/strict";
import { test } from "node:test";
import { measurementSchema, niceTicks, progressSummary } from "./progress";

test("meting: komma-decimalen, lege velden en minimaal één waarde", () => {
  const ok = measurementSchema.parse({ measuredAt: "2026-10-01", weight: "74,6", bodyFat: "", note: "" });
  assert.equal(ok.weight, 74.6);
  assert.equal(ok.bodyFat, null);
  assert.equal(ok.note, null);
  assert.equal(measurementSchema.safeParse({ measuredAt: "2026-10-01", weight: "" }).success, false);
  assert.equal(measurementSchema.safeParse({ measuredAt: "2026-10-01", weight: "7" }).success, false, "onrealistisch gewicht");
  assert.equal(measurementSchema.safeParse({ measuredAt: "gisteren", weight: "70" }).success, false);
});

test("voortgang: verschil eerste en laatste meting per waarde", () => {
  const rows = [
    { measuredAt: new Date("2026-09-01"), weight: 80, bodyFat: 24, waist: null },
    { measuredAt: new Date("2026-08-01"), weight: 82, bodyFat: null, waist: 90 },
    { measuredAt: new Date("2026-10-01"), weight: 78.5, bodyFat: 22.5, waist: null },
  ];
  const s = progressSummary(rows);
  const weight = s.find((f) => f.key === "weight")!;
  assert.equal(weight.points.length, 3);
  assert.equal(weight.latest?.value, 78.5);
  assert.equal(weight.change, -3.5);
  assert.equal(s.find((f) => f.key === "bodyFat")!.change, -1.5);
  assert.equal(s.find((f) => f.key === "waist")!.change, null, "één meting: geen verschil");
  assert.equal(s.some((f) => f.key === "hip"), false);
});

test("astikken zijn ronde getallen rond de data", () => {
  assert.deepEqual(niceTicks(78.5, 82), [78, 79, 80, 81, 82]);
  assert.deepEqual(niceTicks(22.5, 24), [22.5, 23, 23.5, 24]);
  const flat = niceTicks(70, 70);
  assert.ok(flat[0] < 70 && flat.at(-1)! > 70);
});
