/** ISO-weeknummer, bijvoorbeeld "2026-W40". */
export function isoWeekKey(date: Date) {
  const d = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
  const day = d.getUTCDay() || 7;
  d.setUTCDate(d.getUTCDate() + 4 - day);
  const yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
  const week = Math.ceil(((d.getTime() - yearStart.getTime()) / 86400000 + 1) / 7);
  return `${d.getUTCFullYear()}-W${String(week).padStart(2, "0")}`;
}

/**
 * Aantal opeenvolgende weken met een check-in, eindigend in deze week
 * (of vorige week, als deze week nog geen check-in heeft).
 */
export function checkInStreak(weeks: string[], now = new Date()) {
  const done = new Set(weeks);
  const cursor = new Date(now);
  if (!done.has(isoWeekKey(cursor))) cursor.setDate(cursor.getDate() - 7);
  let streak = 0;
  while (done.has(isoWeekKey(cursor))) {
    streak++;
    cursor.setDate(cursor.getDate() - 7);
  }
  return streak;
}
