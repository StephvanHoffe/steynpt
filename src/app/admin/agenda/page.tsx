import { and, asc, eq, gt, lt, ne } from "drizzle-orm";
import { CalendarPlus, CheckCircle2, ChevronLeft, ChevronRight, Settings2 } from "lucide-react";
import type { Metadata } from "next";
import Link from "next/link";
import { ADMIN_PAGE, AdminPageHeader } from "@/components/admin/ui";
import { AgendaList } from "@/components/admin/calendar/AgendaList";
import { AppointmentDrawer } from "@/components/admin/calendar/AppointmentDrawer";
import { DateJump } from "@/components/admin/calendar/DateJump";
import { MonthGrid } from "@/components/admin/calendar/MonthGrid";
import { agendaHref, type AgendaParams, type CalendarEvent, shortName, TYPE_COLOR } from "@/components/admin/calendar/shared";
import { TimeGrid } from "@/components/admin/calendar/TimeGrid";
import { addDays, APPOINTMENT_TYPES, dayToDate, getAgendaLocation, getAppointmentType, isValidDay, weekdayOf, zonedParts, zonedTimeToUtc } from "@/lib/agenda";
import { CALENDAR_VIEWS, gridHours, isoWeek, minutesOfDay, parseView, shiftDay, timeToMinutes, viewDays } from "@/lib/agenda-calendar";
import { requireAdmin } from "@/lib/auth";
import { appointments, availability, blockedPeriods, db, users } from "@/lib/db";

export const metadata: Metadata = { title: "Agenda" };

const fmt = (o: Intl.DateTimeFormatOptions) => new Intl.DateTimeFormat("nl-NL", { timeZone: "Europe/Amsterdam", ...o });
const longDay = fmt({ weekday: "long", day: "numeric", month: "long", year: "numeric" });
const monthYear = fmt({ month: "long", year: "numeric" });
const dayMonth = fmt({ day: "numeric", month: "short" });
const dayMonthYear = fmt({ day: "numeric", month: "short", year: "numeric" });

function periodTitle(view: AgendaParams["view"], days: string[], day: string) {
  const first = dayToDate(days[0]);
  const last = dayToDate(days.at(-1)!);
  if (view === "dag") return { title: longDay.format(dayToDate(day)), eyebrow: `Week ${isoWeek(day)}` };
  if (view === "maand") return { title: monthYear.format(dayToDate(day)), eyebrow: null };
  const title = `${dayMonth.format(first)} – ${dayMonthYear.format(last)}`;
  return { title, eyebrow: view === "week" ? `Week ${isoWeek(days[0])}` : `${days.length} dagen` };
}

const MELDING: Record<string, string> = { gepland: "Afspraak ingepland.", verplaatst: "Afspraak verplaatst. De oude afspraak is geannuleerd." };

export default async function AdminAgendaPage({ searchParams }: PageProps<"/admin/agenda">) {
  await requireAdmin("/admin/agenda");
  const sp = await searchParams;
  const now = new Date();
  const today = zonedParts(now).day;
  const view = parseView(sp.weergave);
  const day = typeof sp.datum === "string" && isValidDay(sp.datum) ? sp.datum : today;
  const cancelled = sp.geannuleerd === "1";
  const params: AgendaParams = { view, day, cancelled };
  const selectedId = Number(sp.afspraak);

  const days = viewDays(view, day);
  const from = zonedTimeToUtc(days[0], "00:00");
  const to = zonedTimeToUtc(addDays(days.at(-1)!, 1), "00:00");

  const [rows, windows, blocks, selected] = await Promise.all([
    db
      .select({ a: appointments, firstName: users.firstName, lastName: users.lastName })
      .from(appointments)
      .innerJoin(users, eq(appointments.userId, users.id))
      .where(and(lt(appointments.startsAt, to), gt(appointments.endsAt, from), cancelled ? undefined : eq(appointments.status, "gepland")))
      .orderBy(asc(appointments.startsAt)),
    db.select().from(availability).orderBy(asc(availability.weekday), asc(availability.startTime)),
    db.select().from(blockedPeriods).where(and(lt(blockedPeriods.startsAt, to), gt(blockedPeriods.endsAt, from))),
    Number.isInteger(selectedId) && selectedId > 0
      ? db
          .select({ a: appointments, u: users })
          .from(appointments)
          .innerJoin(users, eq(appointments.userId, users.id))
          .where(eq(appointments.id, selectedId))
          .then((r) => r[0])
      : Promise.resolve(undefined),
  ]);

  const events: CalendarEvent[] = rows.map(({ a, firstName, lastName }) => {
    const startDay = zonedParts(a.startsAt).day;
    return {
      id: a.id,
      day: startDay,
      start: minutesOfDay(a.startsAt),
      end: zonedParts(a.endsAt).day === startDay ? minutesOfDay(a.endsAt) : 24 * 60,
      startsAt: a.startsAt,
      endsAt: a.endsAt,
      type: a.type,
      typeLabel: getAppointmentType(a.type)?.label ?? a.type,
      location: a.location,
      client: shortName(firstName, lastName),
      cancelled: a.status === "geannuleerd",
      href: agendaHref(params, { afspraak: a.id }),
    };
  });

  // Vrije periodes per dag (in minuten), voor rooster, maand en lijst.
  const dayBlocks = days.flatMap((d) => {
    const start = zonedTimeToUtc(d, "00:00");
    const end = zonedTimeToUtc(addDays(d, 1), "00:00");
    return blocks
      .filter((b) => b.startsAt < end && b.endsAt > start)
      .map((b) => ({
        day: d,
        start: b.startsAt <= start ? 0 : minutesOfDay(b.startsAt),
        end: b.endsAt >= end ? 24 * 60 : minutesOfDay(b.endsAt),
        reason: b.reason,
      }));
  });
  const blockedDays = new Map(dayBlocks.map((b) => [b.day, b.reason]));

  const weekdays = new Set(days.map(weekdayOf));
  const hours = gridHours([
    ...windows.filter((w) => weekdays.has(w.weekday)).map((w) => ({ start: timeToMinutes(w.startTime), end: timeToMinutes(w.endTime) })),
    ...events,
  ]);

  // Aandachtspunten bij de geselecteerde afspraak.
  const warnings: string[] = [];
  if (selected && selected.a.status === "gepland") {
    const { a } = selected;
    const start = minutesOfDay(a.startsAt);
    const end = minutesOfDay(a.endsAt);
    const fits = windows.some(
      (w) => w.weekday === weekdayOf(zonedParts(a.startsAt).day) && w.location === a.location && timeToMinutes(w.startTime) <= start && timeToMinutes(w.endTime) >= end,
    );
    if (!fits) warnings.push(`Valt buiten je beschikbaarheid voor ${getAgendaLocation(a.location)?.label ?? a.location}.`);
    const [block] = await db.select().from(blockedPeriods).where(and(lt(blockedPeriods.startsAt, a.endsAt), gt(blockedPeriods.endsAt, a.startsAt))).limit(1);
    if (block) warnings.push(`Valt in een vrije periode${block.reason ? ` (${block.reason})` : ""}.`);
    const [overlap] = await db
      .select({ id: appointments.id })
      .from(appointments)
      .where(and(ne(appointments.id, a.id), eq(appointments.status, "gepland"), lt(appointments.startsAt, a.endsAt), gt(appointments.endsAt, a.startsAt)))
      .limit(1);
    if (overlap) warnings.push("Overlapt met een andere afspraak.");
  }

  const { title, eyebrow } = periodTitle(view, days, day);
  const planned = events.filter((e) => !e.cancelled).length;
  const newHref = `/admin/agenda/nieuw?datum=${day < today ? today : day}`;
  const melding = typeof sp.melding === "string" ? MELDING[sp.melding] : undefined;
  const nav = "grid h-9 place-items-center rounded-lg border border-line bg-white px-2.5 text-sm font-medium hover:border-ink";

  return (
    <div className={ADMIN_PAGE}>
      <AdminPageHeader
        title="Agenda"
        description={
          <>
            {planned} {planned === 1 ? "afspraak" : "afspraken"} in deze {view === "dag" ? "dag" : view === "week" ? "week" : view === "maand" ? "maand" : "periode"}
          </>
        }
        actions={
          <>
            <Link href="/admin/agenda/instellingen" className="btn btn-sm btn-outline">
              <Settings2 className="size-4" aria-hidden="true" /> Beschikbaarheid
            </Link>
            <Link href={newHref} className="btn btn-sm btn-primary">
              <CalendarPlus className="size-4" aria-hidden="true" /> Nieuwe afspraak
            </Link>
          </>
        }
      />

      {melding && (
        <p className="mb-4 flex items-center gap-2 rounded-lg border border-success/30 bg-success/5 px-4 py-2.5 text-sm font-medium" role="status">
          <CheckCircle2 className="size-4 text-success" aria-hidden="true" /> {melding}
        </p>
      )}

      {/* Werkbalk */}
      <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div className="flex flex-wrap items-center gap-2">
          <Link href={agendaHref({ ...params, day: today })} className={nav}>
            Vandaag
          </Link>
          <div className="flex">
            <Link href={agendaHref({ ...params, day: shiftDay(view, day, -1) })} className={`${nav} rounded-r-none`} aria-label="Vorige periode">
              <ChevronLeft className="size-4" aria-hidden="true" />
            </Link>
            <Link href={agendaHref({ ...params, day: shiftDay(view, day, 1) })} className={`${nav} -ml-px rounded-l-none`} aria-label="Volgende periode">
              <ChevronRight className="size-4" aria-hidden="true" />
            </Link>
          </div>
          <div className="ml-1">
            {eyebrow && <p className="text-xs font-medium uppercase tracking-wide text-muted">{eyebrow}</p>}
            <h2 className="text-lg font-semibold leading-tight first-letter:uppercase">{title}</h2>
          </div>
        </div>
        <div className="flex flex-wrap items-center gap-2">
          <DateJump day={day} view={view} cancelled={cancelled} />
          <nav aria-label="Weergave" className="flex rounded-lg border border-line bg-white p-0.5">
            {CALENDAR_VIEWS.map((v) => (
              <Link
                key={v.id}
                href={agendaHref({ ...params, view: v.id })}
                aria-current={v.id === view ? "page" : undefined}
                className={`rounded-md px-3 py-1.5 text-sm font-medium ${v.id === view ? "bg-ink text-white" : "text-ink/80 hover:bg-surface"}`}
              >
                {v.label}
              </Link>
            ))}
          </nav>
        </div>
      </div>

      {/* Weergave */}
      {view === "maand" ? (
        <MonthGrid params={params} days={days} events={events} blockedDays={blockedDays} today={today} />
      ) : view === "lijst" ? (
        <AgendaList days={days} events={events} today={today} blockedDays={blockedDays} />
      ) : view === "dag" ? (
        <div className="grid gap-4 xl:grid-cols-[1fr_22rem]">
          <TimeGrid params={params} days={days} events={events} windows={windows} blocks={dayBlocks} hours={hours} today={today} nowMinutes={minutesOfDay(now)} now={now} />
          <div className="min-w-0">
            <AgendaList days={days} events={events} today={today} blockedDays={blockedDays} />
          </div>
        </div>
      ) : (
        <TimeGrid params={params} days={days} events={events} windows={windows} blocks={dayBlocks} hours={hours} today={today} nowMinutes={minutesOfDay(now)} now={now} />
      )}

      {/* Legenda en filter */}
      <div className="mt-4 flex flex-wrap items-center justify-between gap-3 text-xs text-muted">
        <ul className="flex flex-wrap items-center gap-x-4 gap-y-1.5">
          {APPOINTMENT_TYPES.map((t) => (
            <li key={t.id} className="flex items-center gap-1.5">
              <span className="size-2.5 rounded-full" style={{ background: TYPE_COLOR[t.id] }} aria-hidden="true" /> {t.label}
            </li>
          ))}
          {view !== "maand" && view !== "lijst" && (
            <li className="flex items-center gap-1.5">
              <span className="size-2.5 rounded-sm border border-line bg-white" aria-hidden="true" /> Beschikbaar
              <span className="ml-2 size-2.5 rounded-sm bg-[#e9ebed]" aria-hidden="true" /> Niet beschikbaar
            </li>
          )}
        </ul>
        <Link href={agendaHref({ ...params, cancelled: !cancelled })} className="inline-flex items-center gap-2 font-medium text-ink hover:underline" role="switch" aria-checked={cancelled}>
          <span className={`relative h-4 w-7 rounded-full transition-colors ${cancelled ? "bg-ink" : "bg-line"}`} aria-hidden="true">
            <span className={`absolute top-0.5 size-3 rounded-full bg-white transition-all ${cancelled ? "left-3.5" : "left-0.5"}`} />
          </span>
          Geannuleerde afspraken tonen
        </Link>
      </div>

      {selected && (
        <AppointmentDrawer
          appointment={selected.a}
          client={selected.u}
          closeHref={agendaHref(params)}
          warnings={warnings}
          now={now}
        />
      )}
    </div>
  );
}
