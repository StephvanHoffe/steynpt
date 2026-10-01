import assert from "node:assert/strict";
import { test } from "node:test";
import { addDays, buildIcs, computeSlots, escapeIcsText, foldIcsLine, getAppointmentType, weekdayOf, zonedParts, zonedTimeToUtc } from "./agenda";

test("tijdzone: zomer- en wintertijd Amsterdam", () => {
  assert.equal(zonedTimeToUtc("2026-10-05", "07:00").toISOString(), "2026-10-05T05:00:00.000Z");
  assert.equal(zonedTimeToUtc("2026-11-02", "07:00").toISOString(), "2026-11-02T06:00:00.000Z");
  assert.equal(zonedTimeToUtc("2026-03-29", "10:00").toISOString(), "2026-03-29T08:00:00.000Z");
  assert.equal(zonedTimeToUtc("2026-03-28", "10:00").toISOString(), "2026-03-28T09:00:00.000Z");
  assert.deepEqual(zonedParts(new Date("2026-10-25T01:30:00Z")), { day: "2026-10-25", time: "02:30", weekday: 7 });
  assert.deepEqual(zonedParts(new Date("2026-10-04T22:30:00Z")), { day: "2026-10-05", time: "00:30", weekday: 1 });
});

test("datums: weekdag en dagen optellen over maandgrenzen", () => {
  assert.equal(weekdayOf("2026-10-05"), 1);
  assert.equal(weekdayOf("2026-10-11"), 7);
  assert.equal(addDays("2026-10-30", 3), "2026-11-02");
  assert.equal(addDays("2026-12-31", 1), "2027-01-01");
});

const pt = getAppointmentType("personal-training")!;
const windows = [{ weekday: 1, startTime: "07:00", endTime: "10:00", location: "gymbase" }];
const now = new Date("2026-10-01T08:00:00Z");
const times = (slots: Date[]) => slots.map((s) => zonedParts(s).time);

test("slots: binnen het venster, passend bij de duur", () => {
  assert.deepEqual(times(computeSlots({ type: pt, location: "gymbase", day: "2026-10-05", windows, busy: [], now })), ["07:00", "07:30", "08:00", "08:30", "09:00"]);
});

test("slots: bestaande afspraken en blokkades worden overgeslagen", () => {
  const busy = [{ startsAt: zonedTimeToUtc("2026-10-05", "08:00"), endsAt: zonedTimeToUtc("2026-10-05", "09:00") }];
  assert.deepEqual(times(computeSlots({ type: pt, location: "gymbase", day: "2026-10-05", windows, busy, now })), ["07:00", "09:00"]);
});

test("slots: minimale aanmeldtijd, horizon, locatie en weekdag", () => {
  const late = new Date("2026-10-04T17:30:00Z"); // zondag 19:30, 12 uur later = maandag 07:30
  assert.deepEqual(times(computeSlots({ type: pt, location: "gymbase", day: "2026-10-05", windows, busy: [], now: late })), ["07:30", "08:00", "08:30", "09:00"]);
  assert.deepEqual(computeSlots({ type: pt, location: "online", day: "2026-10-05", windows, busy: [], now }), [], "PT niet online");
  assert.deepEqual(computeSlots({ type: pt, location: "workout", day: "2026-10-05", windows, busy: [], now }), [], "andere locatie");
  assert.deepEqual(computeSlots({ type: pt, location: "gymbase", day: "2026-10-06", windows, busy: [], now }), [], "dinsdag");
  assert.deepEqual(computeSlots({ type: pt, location: "gymbase", day: "2027-01-04", windows, busy: [], now }), [], "voorbij de horizon");
});

test("iCal: escaping, vouwen en CRLF", () => {
  assert.equal(escapeIcsText("Gymbase, Overtoom; 1\nnotitie\\"), String.raw`Gymbase\, Overtoom\; 1\nnotitie\\`);
  const long = `DESCRIPTION:${"é".repeat(60)}`;
  const folded = foldIcsLine(long);
  for (const line of folded.split("\r\n")) assert.ok(new TextEncoder().encode(line).length <= 75);
  assert.equal(folded.replace(/\r\n /g, ""), long);

  const ics = buildIcs(
    [
      { uid: "appointment-1@steynpt.nl", start: new Date("2026-10-05T05:00:00Z"), end: new Date("2026-10-05T06:00:00Z"), summary: "Personal training – Lisa", location: "Gymbase, Overtoom 371-w" },
      { uid: "appointment-2@steynpt.nl", start: new Date("2026-10-06T05:00:00Z"), end: new Date("2026-10-06T05:30:00Z"), summary: "Meting", cancelled: true },
    ],
    { name: "SteynPT afspraken", now: new Date("2026-10-01T08:00:00Z") },
  );
  assert.ok(ics.startsWith("BEGIN:VCALENDAR\r\nVERSION:2.0\r\n"));
  assert.ok(ics.endsWith("END:VCALENDAR\r\n"));
  assert.match(ics, /DTSTART:20261005T050000Z\r\n/);
  assert.match(ics, /LOCATION:Gymbase\\, Overtoom 371-w\r\n/);
  assert.match(ics, /STATUS:CANCELLED/);
  assert.doesNotMatch(ics.replace(/\r\n/g, ""), /\n/, "alleen CRLF-regeleinden");
});
