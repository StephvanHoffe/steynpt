import { type CalendarView } from "@/lib/agenda-calendar";

/** Kleur per afspraaktype; overal met het label erbij, zodat kleur nooit de enige aanwijzing is. */
export const TYPE_COLOR: Record<string, string> = {
  "personal-training": "#111315",
  kennismaking: "#0b6f78",
  meting: "#b45309",
  "online-call": "#4338ca",
};
export const typeColor = (type: string) => TYPE_COLOR[type] ?? "#5b6168";

export type AgendaParams = { view: CalendarView; day: string; cancelled: boolean };

/** Link binnen de agenda; laat weg wat gelijk is aan de standaard. */
export function agendaHref(p: AgendaParams, extra: { afspraak?: number; melding?: string } = {}) {
  const q = new URLSearchParams();
  if (p.view !== "week") q.set("weergave", p.view);
  q.set("datum", p.day);
  if (p.cancelled) q.set("geannuleerd", "1");
  if (extra.afspraak) q.set("afspraak", String(extra.afspraak));
  if (extra.melding) q.set("melding", extra.melding);
  return `/admin/agenda?${q.toString()}`;
}

/** Een afspraak zoals de weergaven hem tonen. */
export type CalendarEvent = {
  id: number;
  day: string;
  start: number; // minuten sinds middernacht
  end: number;
  startsAt: Date;
  endsAt: Date;
  type: string;
  typeLabel: string;
  location: string;
  client: string;
  cancelled: boolean;
  href: string;
};

export const shortName = (first: string, last: string) => `${first} ${last.split(" ").at(-1)?.[0] ?? ""}.`.trim();
