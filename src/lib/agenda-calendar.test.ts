import assert from "node:assert/strict";
import test from "node:test";
import { addMonths, gridHours, isoWeek, layoutLanes, minutesOfDay, parseView, shiftDay, startOfWeek, viewDays } from "./agenda-calendar";

test("kalender: weergaven en dagen", () => {
  assert.equal(parseView("maand"), "maand");
  assert.equal(parseView("onzin"), "week");
  assert.equal(startOfWeek("2026-10-04"), "2026-09-28", "zondag hoort bij de week vanaf maandag");
  assert.deepEqual(viewDays("dag", "2026-10-05"), ["2026-10-05"]);
  const week = viewDays("week", "2026-10-07");
  assert.equal(week[0], "2026-10-05");
  assert.equal(week.at(-1), "2026-10-11");

  const oktober = viewDays("maand", "2026-10-15");
  assert.equal(oktober[0], "2026-09-28");
  assert.equal(oktober.at(-1), "2026-11-01");
  assert.equal(oktober.length % 7, 0);
  assert.equal(viewDays("maand", "2027-02-10").length, 28, "februari 2027 begint op maandag en past in vier weken");
  assert.equal(viewDays("lijst", "2026-10-01").length, 28);
});

test("kalender: bladeren en weeknummers", () => {
  assert.equal(shiftDay("dag", "2026-10-31", 1), "2026-11-01");
  assert.equal(shiftDay("week", "2026-10-05", -1), "2026-09-28");
  assert.equal(shiftDay("maand", "2026-01-31", 1), "2026-02-01");
  assert.equal(shiftDay("maand", "2026-01-15", -1), "2025-12-01");
  assert.equal(addMonths("2026-12-01", 1), "2027-01-01");
  assert.equal(isoWeek("2026-10-05"), 41);
  assert.equal(isoWeek("2027-01-01"), 53, "1 januari 2027 valt nog in week 53 van 2026");
  assert.equal(isoWeek("2027-01-04"), 1);
});

test("kalender: tijdrooster en overlappende afspraken", () => {
  assert.equal(minutesOfDay(new Date("2026-10-05T05:30:00Z")), 7 * 60 + 30, "zomertijd");
  assert.equal(minutesOfDay(new Date("2026-11-02T06:30:00Z")), 7 * 60 + 30, "wintertijd");
  assert.deepEqual(gridHours([]), { start: 7, end: 21 });
  assert.deepEqual(gridHours([{ start: 6 * 60 + 30, end: 7 * 60 }, { start: 21 * 60, end: 22 * 60 + 15 }]), { start: 6, end: 23 });

  const lanes = layoutLanes([
    { id: 1, start: 480, end: 540 },
    { id: 2, start: 510, end: 540 },
    { id: 3, start: 540, end: 600 },
    { id: 4, start: 600, end: 630 },
    { id: 5, start: 600, end: 660 },
    { id: 6, start: 615, end: 630 },
  ]);
  assert.deepEqual(lanes.get(1), { lane: 0, lanes: 2 });
  assert.deepEqual(lanes.get(2), { lane: 1, lanes: 2 });
  assert.deepEqual(lanes.get(3), { lane: 0, lanes: 1 }, "aansluitend is geen overlap");
  assert.equal(lanes.get(5)!.lanes, 3);
  assert.notEqual(lanes.get(4)!.lane, lanes.get(6)!.lane);
});
