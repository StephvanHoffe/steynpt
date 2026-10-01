import { asc, gt } from "drizzle-orm";
import { Trash2 } from "lucide-react";
import type { Metadata } from "next";
import { AvailabilityForm, BlockForm, CopyField } from "@/components/agenda/AdminAgendaForms";
import { ADMIN_PAGE, AdminPageHeader } from "@/components/admin/ui";
import { BOOKING_RULES, formatDayLong, getAgendaLocation, WEEKDAYS, zonedParts } from "@/lib/agenda";
import { getIcalToken } from "@/lib/agenda-server";
import { deleteAvailabilityAction, deleteBlockedPeriodAction, rotateIcalTokenAction } from "@/lib/actions/agenda";
import { requireAdmin } from "@/lib/auth";
import { availability, blockedPeriods, db } from "@/lib/db";
import { siteOrigin } from "@/lib/origin";

export const metadata: Metadata = { title: "Agenda-instellingen" };

function Section({ id, title, intro, children }: { id: string; title: string; intro: string; children: React.ReactNode }) {
  return (
    <section aria-labelledby={id} className="card p-5 sm:p-6">
      <h2 id={id} className="text-lg font-semibold">
        {title}
      </h2>
      <p className="mt-1 text-sm text-muted">{intro}</p>
      <div className="mt-5">{children}</div>
    </section>
  );
}

export default async function AgendaSettingsPage() {
  await requireAdmin("/admin/agenda/instellingen");
  const now = new Date();
  const [windows, blocks, token, origin] = await Promise.all([
    db.select().from(availability).orderBy(asc(availability.weekday), asc(availability.startTime)),
    db.select().from(blockedPeriods).where(gt(blockedPeriods.endsAt, now)).orderBy(asc(blockedPeriods.startsAt)),
    getIcalToken(),
    siteOrigin(),
  ]);
  const feedUrl = `${origin}/ical/${token}.ics`;
  const webcalUrl = feedUrl.replace(/^https?:/, "webcal:");

  return (
    <div className={ADMIN_PAGE}>
      <AdminPageHeader title="Instellingen" description="Wanneer klanten kunnen boeken, je vrije dagen en de koppeling met je eigen agenda." />

      <div className="grid gap-6 xl:grid-cols-[1.25fr_1fr]">
        <Section
          id="beschikbaar"
          title="Beschikbaarheid per week"
          intro={`Klanten kunnen alleen binnen deze tijden boeken, minimaal ${BOOKING_RULES.minNoticeHours} uur van tevoren en maximaal ${BOOKING_RULES.horizonDays / 7} weken vooruit.`}
        >
          <div className="overflow-hidden rounded-lg border border-line">
            <table className="w-full text-sm">
              <caption className="sr-only">Beschikbaarheid per weekdag</caption>
              <tbody className="divide-y divide-line">
                {WEEKDAYS.map((name, i) => {
                  const dayWindows = windows.filter((w) => w.weekday === i + 1);
                  return (
                    <tr key={name} className="align-top">
                      <th scope="row" className="w-28 bg-[#f6f7f8] px-3 py-2.5 text-left font-medium first-letter:uppercase">
                        {name}
                      </th>
                      <td className="px-3 py-2">
                        {dayWindows.length === 0 ? (
                          <span className="inline-block py-1 text-muted">Niet beschikbaar</span>
                        ) : (
                          <ul className="flex flex-wrap gap-2">
                            {dayWindows.map((w) => (
                              <li key={w.id} className="inline-flex items-center gap-1 rounded-full border border-line bg-white py-0.5 pl-3 pr-1">
                                <span className="tabular-nums">
                                  {w.startTime}–{w.endTime}
                                </span>
                                <span className="text-muted">· {getAgendaLocation(w.location)?.label ?? w.location}</span>
                                <form action={deleteAvailabilityAction}>
                                  <input type="hidden" name="id" value={w.id} />
                                  <button
                                    type="submit"
                                    aria-label={`Verwijder ${name} ${w.startTime}–${w.endTime}`}
                                    className="grid size-7 place-items-center rounded-full text-muted hover:bg-danger/10 hover:text-danger"
                                  >
                                    <Trash2 className="size-3.5" aria-hidden="true" />
                                  </button>
                                </form>
                              </li>
                            ))}
                          </ul>
                        )}
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
          {windows.length === 0 && <p className="mt-3 text-sm font-medium text-danger">Er zijn nog geen tijden ingesteld: klanten kunnen nog niets boeken.</p>}
          <h3 className="mb-3 mt-6 text-sm font-semibold">Tijden toevoegen</h3>
          <AvailabilityForm />
        </Section>

        <div className="grid content-start gap-6">
          <Section id="geblokkeerd" title="Vrije dagen en vakanties" intro="Op deze dagen kunnen klanten niet boeken. Bestaande afspraken blijven staan.">
            <ul className="divide-y divide-line rounded-lg border border-line text-sm">
              {blocks.length === 0 && <li className="p-3 text-muted">Geen vrije dagen gepland.</li>}
              {blocks.map((b) => {
                const lastDay = new Date(b.endsAt.getTime() - 60_000);
                return (
                  <li key={b.id} className="flex items-center justify-between gap-3 px-3 py-2">
                    <span>
                      <span className="font-medium first-letter:uppercase">{formatDayLong(b.startsAt)}</span>
                      {zonedParts(b.startsAt).day !== zonedParts(lastDay).day && <> t/m {formatDayLong(lastDay)}</>}
                      {b.reason && <span className="text-muted"> · {b.reason}</span>}
                    </span>
                    <form action={deleteBlockedPeriodAction}>
                      <input type="hidden" name="id" value={b.id} />
                      <button type="submit" aria-label="Verwijder blokkade" className="grid size-8 place-items-center rounded-md text-muted hover:bg-danger/10 hover:text-danger">
                        <Trash2 className="size-4" aria-hidden="true" />
                      </button>
                    </form>
                  </li>
                );
              })}
            </ul>
            <div className="mt-5">
              <BlockForm today={zonedParts(now).day} />
            </div>
          </Section>

          <Section id="ical" title="Koppelen met je eigen agenda" intro="Abonneer je op deze link; nieuwe en geannuleerde afspraken verschijnen dan vanzelf in je agenda.">
            <CopyField value={feedUrl} label="iCal-link" />
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
          </Section>
        </div>
      </div>
    </div>
  );
}
