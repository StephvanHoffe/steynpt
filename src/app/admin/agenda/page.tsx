import { asc, eq, gt } from "drizzle-orm";
import { ArrowLeft, Trash2 } from "lucide-react";
import type { Metadata } from "next";
import { headers } from "next/headers";
import Link from "next/link";
import { AvailabilityForm, BlockForm, CopyField } from "@/components/agenda/AdminAgendaForms";
import { formatDayLong, formatTime, getAgendaLocation, getAppointmentType, WEEKDAYS, zonedParts } from "@/lib/agenda";
import { getIcalToken } from "@/lib/agenda-server";
import { cancelAppointmentAction, deleteAvailabilityAction, deleteBlockedPeriodAction, rotateIcalTokenAction } from "@/lib/actions/agenda";
import { requireAdmin } from "@/lib/auth";
import { appointments, availability, blockedPeriods, db, users } from "@/lib/db";
import { SITE } from "@/lib/site";

export const metadata: Metadata = { title: "Agenda beheren", robots: { index: false } };

export default async function AdminAgendaPage() {
  await requireAdmin("/admin/agenda");
  const now = new Date();
  const [upcoming, windows, blocks, token, headerList] = await Promise.all([
    db
      .select({ a: appointments, firstName: users.firstName, lastName: users.lastName, phone: users.phone, email: users.email, userId: users.id })
      .from(appointments)
      .innerJoin(users, eq(appointments.userId, users.id))
      .where(gt(appointments.endsAt, now))
      .orderBy(asc(appointments.startsAt))
      .limit(200),
    db.select().from(availability).orderBy(asc(availability.weekday), asc(availability.startTime)),
    db.select().from(blockedPeriods).where(gt(blockedPeriods.endsAt, now)).orderBy(asc(blockedPeriods.startsAt)),
    getIcalToken(),
    headers(),
  ]);

  // Gebruik het echte domein voor de abonnementslink (ook lokaal en op preview-omgevingen).
  const host = headerList.get("x-forwarded-host") ?? headerList.get("host");
  const proto = headerList.get("x-forwarded-proto") ?? (host?.startsWith("localhost") ? "http" : "https");
  const base = host ? `${proto}://${host}` : SITE.url;
  const feedUrl = `${base}/ical/${token}.ics`;
  const webcalUrl = feedUrl.replace(/^https?:/, "webcal:");

  const byDay = new Map<string, typeof upcoming>();
  for (const row of upcoming) {
    const day = zonedParts(row.a.startsAt).day;
    byDay.set(day, [...(byDay.get(day) ?? []), row]);
  }

  return (
    <div className="container-site py-10 lg:py-14">
      <Link href="/admin" className="inline-flex items-center gap-1.5 text-sm font-semibold text-muted hover:text-ink">
        <ArrowLeft className="size-4" aria-hidden="true" /> Beheer
      </Link>
      <h1 className="display display-lg mt-3">Agenda</h1>

      <div className="mt-8 grid gap-8 xl:grid-cols-[1.4fr_1fr]">
        <section aria-labelledby="afspraken" className="grid content-start gap-4">
          <h2 id="afspraken" className="display display-sm">
            Komende afspraken
          </h2>
          {byDay.size === 0 && <p className="text-muted">Nog geen afspraken.</p>}
          {[...byDay.entries()].map(([day, rows]) => (
            <div key={day} className="card overflow-hidden">
              <h3 className="border-b border-line bg-surface px-5 py-3 text-sm font-semibold first-letter:uppercase">{formatDayLong(rows[0].a.startsAt)}</h3>
              <ul className="divide-y divide-line">
                {rows.map(({ a, firstName, lastName, phone, email, userId }) => (
                  <li key={a.id} className={`flex flex-col gap-2 px-5 py-4 sm:flex-row sm:items-start sm:justify-between ${a.status === "geannuleerd" ? "opacity-55" : ""}`}>
                    <div className="text-sm">
                      <p className="font-semibold tabular-nums">
                        {formatTime(a.startsAt)}–{formatTime(a.endsAt)} · {getAppointmentType(a.type)?.label ?? a.type}
                        {a.status === "geannuleerd" && <span className="ml-2 rounded bg-surface px-1.5 py-0.5 text-xs font-medium">geannuleerd door {a.cancelledBy}</span>}
                      </p>
                      <p className="mt-0.5 text-muted">
                        <Link href={`/admin/leden/${userId}`} className="font-medium text-ink underline decoration-accent underline-offset-4">
                          {firstName} {lastName}
                        </Link>{" "}
                        · {getAgendaLocation(a.location)?.label ?? a.location} · {phone ?? email}
                      </p>
                      {a.note && <p className="mt-1 text-muted">&ldquo;{a.note}&rdquo;</p>}
                    </div>
                    {a.status === "gepland" && (
                      <form action={cancelAppointmentAction}>
                        <input type="hidden" name="id" value={a.id} />
                        <button type="submit" className="btn btn-sm btn-outline">
                          Annuleren
                        </button>
                      </form>
                    )}
                  </li>
                ))}
              </ul>
            </div>
          ))}
        </section>

        <div className="grid content-start gap-8">
          <section aria-labelledby="ical" className="card p-6">
            <h2 id="ical" className="display text-xl">
              Koppelen met je eigen agenda
            </h2>
            <p className="mt-2 text-sm text-muted">Abonneer je op deze link; nieuwe en geannuleerde afspraken verschijnen dan automatisch in je agenda.</p>
            <div className="mt-4">
              <CopyField value={feedUrl} label="iCal-link" />
            </div>
            <div className="mt-5 grid gap-4 text-sm">
              <div>
                <p className="font-semibold">Google Agenda</p>
                <p className="text-muted">
                  Ga op een computer naar calendar.google.com › Andere agenda&apos;s › + › Via URL, plak de link en kies Agenda toevoegen. Google ververst
                  geabonneerde agenda&apos;s zelf, meestal enkele keren per dag.
                </p>
              </div>
              <div>
                <p className="font-semibold">Apple Agenda (iPhone, iPad, Mac)</p>
                <p className="text-muted">
                  Open{" "}
                  <a href={webcalUrl} className="font-medium text-ink underline decoration-accent underline-offset-4">
                    deze link op je iPhone of Mac
                  </a>{" "}
                  en kies Abonneer. Zet bij Vernieuw automatisch bijvoorbeeld &ldquo;Elk uur&rdquo;.
                </p>
              </div>
              <p className="rounded-lg bg-surface p-3 text-xs text-muted">
                De link bevat namen en telefoonnummers van klanten. Deel hem met niemand. Uitgelekt? Maak een nieuwe link; de oude werkt dan direct niet meer.
              </p>
              <form action={rotateIcalTokenAction}>
                <button type="submit" className="btn btn-sm btn-outline">
                  Nieuwe link maken
                </button>
              </form>
            </div>
          </section>

          <section aria-labelledby="beschikbaar" className="card p-6">
            <h2 id="beschikbaar" className="display text-xl">
              Beschikbaarheid per week
            </h2>
            <p className="mt-2 text-sm text-muted">Klanten kunnen alleen binnen deze tijden boeken, minimaal 12 uur van tevoren en maximaal 6 weken vooruit.</p>
            <ul className="mt-4 divide-y divide-line rounded-lg border border-line text-sm">
              {windows.length === 0 && <li className="p-3 text-muted">Nog geen tijden ingesteld: klanten kunnen nog niets boeken.</li>}
              {windows.map((w) => (
                <li key={w.id} className="flex items-center justify-between gap-3 px-3 py-2">
                  <span>
                    <span className="inline-block w-24 font-medium first-letter:uppercase">{WEEKDAYS[w.weekday - 1]}</span>
                    <span className="tabular-nums">
                      {w.startTime}–{w.endTime}
                    </span>{" "}
                    · {getAgendaLocation(w.location)?.label ?? w.location}
                  </span>
                  <form action={deleteAvailabilityAction}>
                    <input type="hidden" name="id" value={w.id} />
                    <button type="submit" aria-label={`Verwijder ${WEEKDAYS[w.weekday - 1]} ${w.startTime}`} className="grid size-8 place-items-center rounded-md text-muted hover:bg-surface hover:text-danger">
                      <Trash2 className="size-4" aria-hidden="true" />
                    </button>
                  </form>
                </li>
              ))}
            </ul>
            <div className="mt-5">
              <AvailabilityForm />
            </div>
          </section>

          <section aria-labelledby="geblokkeerd" className="card p-6">
            <h2 id="geblokkeerd" className="display text-xl">
              Vrije dagen en vakanties
            </h2>
            <ul className="mt-4 divide-y divide-line rounded-lg border border-line text-sm">
              {blocks.length === 0 && <li className="p-3 text-muted">Geen geblokkeerde periodes.</li>}
              {blocks.map((b) => (
                <li key={b.id} className="flex items-center justify-between gap-3 px-3 py-2">
                  <span>
                    {formatDayLong(b.startsAt)}
                    {zonedParts(b.startsAt).day !== zonedParts(new Date(b.endsAt.getTime() - 60_000)).day &&
                      ` t/m ${formatDayLong(new Date(b.endsAt.getTime() - 60_000))}`}
                    {b.reason ? ` · ${b.reason}` : ""}
                  </span>
                  <form action={deleteBlockedPeriodAction}>
                    <input type="hidden" name="id" value={b.id} />
                    <button type="submit" aria-label="Verwijder blokkade" className="grid size-8 place-items-center rounded-md text-muted hover:bg-surface hover:text-danger">
                      <Trash2 className="size-4" aria-hidden="true" />
                    </button>
                  </form>
                </li>
              ))}
            </ul>
            <div className="mt-5">
              <BlockForm today={zonedParts(now).day} />
            </div>
          </section>
        </div>
      </div>
    </div>
  );
}
