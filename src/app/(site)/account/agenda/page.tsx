import { and, asc, eq, gt } from "drizzle-orm";
import { ArrowLeft, CalendarCheck2, Info } from "lucide-react";
import type { Metadata } from "next";
import Link from "next/link";
import { AppointmentList } from "@/components/agenda/AppointmentList";
import { BookingForm } from "@/components/agenda/BookingForm";
import { APPOINTMENT_TYPES, BOOKING_RULES, dayToDate, formatDayLong, formatDayShort, formatTime, getAgendaLocation, getAppointmentType, isValidDay } from "@/lib/agenda";
import { availableDays, locationsWithAvailability, slotsForDay } from "@/lib/agenda-server";
import { requireUser } from "@/lib/auth";
import { appointments, db } from "@/lib/db";

export const metadata: Metadata = { title: "Agenda" };

const qs = (params: Record<string, string | undefined>) =>
  "?" + new URLSearchParams(Object.entries(params).filter((e): e is [string, string] => !!e[1])).toString();

function Step({ n, title, children }: { n: number; title: string; children: React.ReactNode }) {
  return (
    <section className="card p-6">
      <h2 className="flex items-center gap-3 font-semibold">
        <span className="grid size-7 place-items-center rounded-md bg-ink text-xs text-white">{n}</span>
        {title}
      </h2>
      <div className="mt-4">{children}</div>
    </section>
  );
}

export default async function AgendaPage({ searchParams }: PageProps<"/account/agenda">) {
  const user = await requireUser("/account/agenda");
  const params = await searchParams;
  const now = new Date();

  const upcoming = await db
    .select()
    .from(appointments)
    .where(and(eq(appointments.userId, user.id), eq(appointments.status, "gepland"), gt(appointments.endsAt, now)))
    .orderBy(asc(appointments.startsAt));
  const booked = typeof params.geboekt === "string" ? upcoming.find((a) => a.id === Number(params.geboekt)) : undefined;

  const type = getAppointmentType(typeof params.type === "string" ? params.type : null);
  const locations = type ? await locationsWithAvailability(type) : [];
  const requested = typeof params.locatie === "string" ? params.locatie : undefined;
  const location = type && requested && locations.includes(requested as never) ? requested : locations.length === 1 ? locations[0] : undefined;
  const days = type && location ? await availableDays(type, location, now) : [];
  const day = isValidDay(params.datum) && days.some((d) => d.day === params.datum) ? params.datum : undefined;
  const slots = type && location && day ? await slotsForDay(type, location, day, now) : [];
  const blockedForUser = (t: (typeof APPOINTMENT_TYPES)[number]) => "requiresCoaching" in t && t.requiresCoaching && user.coachingStatus !== "actief";

  return (
    <div className="container-site max-w-4xl py-10 lg:py-14">
      <p className="eyebrow text-accent">Mijn omgeving</p>
      <h1 className="display display-lg mt-3">Agenda</h1>

      {booked && (
        <p role="status" className="mt-6 flex items-start gap-3 rounded-lg border border-success/30 bg-success/5 p-4 text-sm text-success">
          <CalendarCheck2 className="size-5 shrink-0" aria-hidden="true" />
          <span>
            Je afspraak is bevestigd: {getAppointmentType(booked.type)?.label} op {formatDayLong(booked.startsAt)} om {formatTime(booked.startsAt)}.{" "}
            <a href={`/account/agenda/${booked.id}/ics`} className="font-semibold underline">
              Zet hem in je agenda
            </a>
            .
          </span>
        </p>
      )}

      <section className="mt-8" aria-labelledby="komend">
        <h2 id="komend" className="display display-sm">
          Komende afspraken
        </h2>
        <div className="mt-4">
          <AppointmentList items={upcoming} now={now} />
        </div>
      </section>

      <section className="mt-12 grid gap-4" aria-labelledby="nieuw">
        <h2 id="nieuw" className="display display-sm">
          Afspraak maken
        </h2>

        <Step n={1} title="Wat wil je plannen?">
          <div className="grid gap-2 sm:grid-cols-2">
            {APPOINTMENT_TYPES.map((t) => {
              const selected = type?.id === t.id;
              const disabled = blockedForUser(t);
              const className = `block rounded-lg border p-4 transition-colors ${selected ? "border-ink ring-1 ring-ink" : "border-line"} ${disabled ? "cursor-not-allowed opacity-60" : "hover:border-ink"}`;
              const body = (
                <>
                  <span className="flex items-baseline justify-between gap-2">
                    <span className="font-semibold">{t.label}</span>
                    <span className="text-xs text-muted">{t.minutes} min</span>
                  </span>
                  <span className="mt-1 block text-sm text-muted">{disabled ? "Alleen voor klanten met actieve online coaching." : t.description}</span>
                </>
              );
              return disabled ? (
                <div key={t.id} className={className} aria-disabled="true">
                  {body}
                </div>
              ) : (
                <Link key={t.id} href={qs({ type: t.id })} scroll={false} aria-current={selected ? "true" : undefined} className={className}>
                  {body}
                </Link>
              );
            })}
          </div>
        </Step>

        {type && locations.length === 0 && (
          <p className="flex gap-2 rounded-lg bg-surface p-4 text-sm">
            <Info className="size-5 shrink-0" aria-hidden="true" />
            Er zijn op dit moment geen tijden beschikbaar voor dit soort afspraak.{" "}
            <Link href="/contact" className="font-semibold underline">
              Neem contact op
            </Link>
            .
          </p>
        )}

        {type && locations.length > 1 && (
          <Step n={2} title="Waar?">
            <div className="flex flex-wrap gap-2">
              {locations.map((l) => (
                <Link
                  key={l}
                  href={qs({ type: type.id, locatie: l })}
                  scroll={false}
                  aria-current={location === l ? "true" : undefined}
                  className={`rounded-lg border px-4 py-2.5 text-sm font-medium transition-colors ${location === l ? "border-ink bg-ink text-white" : "border-line hover:border-ink"}`}
                >
                  {getAgendaLocation(l)?.label}
                </Link>
              ))}
            </div>
          </Step>
        )}

        {type && location && (
          <Step n={locations.length > 1 ? 3 : 2} title="Welke dag?">
            {days.length === 0 ? (
              <p className="text-sm text-muted">De komende {BOOKING_RULES.horizonDays / 7} weken zijn er geen tijden vrij op deze locatie.</p>
            ) : (
              <div className="flex flex-wrap gap-2">
                {days.map((d) => (
                  <Link
                    key={d.day}
                    href={qs({ type: type.id, locatie: location, datum: d.day })}
                    scroll={false}
                    aria-current={day === d.day ? "date" : undefined}
                    className={`rounded-lg border px-3 py-2 text-sm transition-colors ${day === d.day ? "border-ink bg-ink text-white" : "border-line hover:border-ink"}`}
                  >
                    <span className="font-medium first-letter:uppercase">{formatDayShort(dayToDate(d.day))}</span>
                  </Link>
                ))}
              </div>
            )}
          </Step>
        )}

        {type && location && day && (
          <Step n={locations.length > 1 ? 4 : 3} title={`Tijd op ${formatDayLong(dayToDate(day))}`}>
            {slots.length === 0 ? (
              <p className="text-sm text-muted">Deze dag is inmiddels vol. Kies een andere dag.</p>
            ) : (
              <BookingForm type={type.id} location={location} slots={slots.map((s) => ({ value: s.toISOString(), label: formatTime(s) }))} />
            )}
            <p className="mt-4 text-xs text-muted">
              {getAgendaLocation(location)?.label}: {getAgendaLocation(location)?.address}. Afzeggen kan tot {BOOKING_RULES.cancelUntilHours} uur van tevoren.
            </p>
          </Step>
        )}
      </section>

      <Link href="/account" className="mt-10 inline-flex items-center gap-1.5 text-sm font-semibold text-muted hover:text-ink">
        <ArrowLeft className="size-4" aria-hidden="true" /> Terug naar mijn omgeving
      </Link>
    </div>
  );
}
