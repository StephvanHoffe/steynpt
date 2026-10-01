import { AlertTriangle, CalendarClock, CalendarPlus, Mail, MapPin, Phone, StickyNote, UserRound, X } from "lucide-react";
import Link from "next/link";
import { formatDayLong, formatTime, getAgendaLocation, getAppointmentType } from "@/lib/agenda";
import { cancelAppointmentAction } from "@/lib/actions/agenda";
import type { Appointment, CoachingStatus } from "@/lib/db";
import { getOnlinePlan } from "@/lib/site";
import { CoachingBadge } from "../ui";
import { CloseOnEscape } from "./CloseOnEscape";
import { typeColor } from "./shared";

type Client = { id: string; firstName: string; lastName: string; email: string; phone: string | null; coachingStatus: CoachingStatus; plan: string | null };

/** Paneel met alle gegevens van één afspraak, met annuleren en verplaatsen. */
export function AppointmentDrawer({
  appointment: a,
  client,
  closeHref,
  warnings,
  now,
}: {
  appointment: Appointment;
  client: Client;
  closeHref: string;
  warnings: string[];
  now: Date;
}) {
  const type = getAppointmentType(a.type);
  const location = getAgendaLocation(a.location);
  const color = typeColor(a.type);
  const upcoming = a.status === "gepland" && a.endsAt > now;
  const name = `${client.firstName} ${client.lastName}`;

  return (
    <div className="fixed inset-0 z-50 flex items-end justify-end sm:items-stretch" role="dialog" aria-modal="true" aria-labelledby="afspraak-titel">
      <CloseOnEscape href={closeHref} />
      <Link href={closeHref} scroll={false} className="absolute inset-0 bg-ink/30" aria-label="Sluiten" tabIndex={-1} />
      <div className="relative flex max-h-[88dvh] w-full flex-col overflow-y-auto rounded-t-2xl bg-white shadow-2xl sm:max-h-none sm:max-w-md sm:rounded-none">
        <div className="h-1.5 shrink-0" style={{ background: color }} aria-hidden="true" />
        <div className="flex items-start justify-between gap-3 border-b border-line px-6 py-5">
          <div>
            <p className="text-xs font-semibold uppercase tracking-wider" style={{ color }}>
              {type?.label ?? a.type}
            </p>
            <h2 id="afspraak-titel" className="display mt-1 text-2xl">
              {name}
            </h2>
            {a.status === "geannuleerd" && (
              <p className="mt-2 inline-flex rounded-full bg-danger/10 px-2.5 py-0.5 text-xs font-semibold text-danger">
                Geannuleerd door {a.cancelledBy === "klant" ? "de klant" : "jou"}
              </p>
            )}
          </div>
          <Link href={closeHref} scroll={false} className="grid size-9 shrink-0 place-items-center rounded-lg text-muted hover:bg-surface hover:text-ink" aria-label="Sluiten">
            <X className="size-5" aria-hidden="true" />
          </Link>
        </div>

        <dl className="grid gap-4 px-6 py-5 text-sm">
          <div className="flex gap-3">
            <dt>
              <CalendarClock className="size-5 text-muted" aria-hidden="true" />
              <span className="sr-only">Wanneer</span>
            </dt>
            <dd>
              <span className="block font-semibold first-letter:uppercase">{formatDayLong(a.startsAt)}</span>
              <span className="tabular-nums text-muted">
                {formatTime(a.startsAt)}–{formatTime(a.endsAt)} · {type?.minutes ?? Math.round((a.endsAt.getTime() - a.startsAt.getTime()) / 60000)} minuten
              </span>
            </dd>
          </div>
          <div className="flex gap-3">
            <dt>
              <MapPin className="size-5 text-muted" aria-hidden="true" />
              <span className="sr-only">Waar</span>
            </dt>
            <dd>
              <span className="block font-semibold">{location?.label ?? a.location}</span>
              {location && <span className="text-muted">{location.address}</span>}
            </dd>
          </div>
          <div className="flex gap-3">
            <dt>
              <UserRound className="size-5 text-muted" aria-hidden="true" />
              <span className="sr-only">Klant</span>
            </dt>
            <dd className="grid gap-1">
              <Link href={`/admin/leden/${client.id}`} className="font-semibold underline decoration-accent underline-offset-4">
                {name}
              </Link>
              <span className="flex flex-wrap items-center gap-2">
                <CoachingBadge status={client.coachingStatus} />
                {client.plan && <span className="text-xs text-muted">{getOnlinePlan(client.plan)?.name}</span>}
              </span>
              {client.phone && (
                <a href={`tel:${client.phone.replace(/\s/g, "")}`} className="inline-flex items-center gap-1.5 text-ink hover:underline">
                  <Phone className="size-3.5 text-muted" aria-hidden="true" /> {client.phone}
                </a>
              )}
              <a href={`mailto:${client.email}`} className="inline-flex items-center gap-1.5 break-all text-ink hover:underline">
                <Mail className="size-3.5 shrink-0 text-muted" aria-hidden="true" /> {client.email}
              </a>
            </dd>
          </div>
          {a.note && (
            <div className="flex gap-3">
              <dt>
                <StickyNote className="size-5 text-muted" aria-hidden="true" />
                <span className="sr-only">Opmerking</span>
              </dt>
              <dd className="whitespace-pre-line rounded-lg bg-surface px-3 py-2">{a.note}</dd>
            </div>
          )}
        </dl>

        {warnings.length > 0 && (
          <ul className="mx-6 mb-5 grid gap-1.5 rounded-lg border border-[#b45309]/30 bg-[#fdf6ec] p-3 text-sm">
            {warnings.map((w) => (
              <li key={w} className="flex gap-2">
                <AlertTriangle className="mt-0.5 size-4 shrink-0 text-[#b45309]" aria-hidden="true" /> {w}
              </li>
            ))}
          </ul>
        )}

        <div className="mt-auto grid gap-2 border-t border-line px-6 py-5">
          {upcoming && (
            <Link href={`/admin/agenda/nieuw?verplaats=${a.id}`} className="btn btn-primary">
              <CalendarClock className="size-4" aria-hidden="true" /> Verplaatsen
            </Link>
          )}
          <Link href={`/admin/agenda/nieuw?lid=${client.id}`} className="btn btn-outline">
            <CalendarPlus className="size-4" aria-hidden="true" /> Nieuwe afspraak met {client.firstName}
          </Link>
          {upcoming && (
            <details className="group rounded-lg border border-line">
              <summary className="cursor-pointer list-none px-4 py-2.5 text-center text-sm font-semibold text-danger [&::-webkit-details-marker]:hidden">
                Afspraak annuleren…
              </summary>
              <div className="border-t border-line p-4 text-sm">
                <p className="text-muted">
                  {client.firstName} ziet de afspraak daarna als geannuleerd in Mijn omgeving. Er gaat geen e-mail uit, dus laat het {client.firstName} ook zelf weten.
                </p>
                <form action={cancelAppointmentAction} className="mt-3">
                  <input type="hidden" name="id" value={a.id} />
                  <button type="submit" className="btn btn-sm w-full border border-danger bg-danger text-white hover:bg-danger/90">
                    Ja, annuleer deze afspraak
                  </button>
                </form>
              </div>
            </details>
          )}
        </div>
      </div>
    </div>
  );
}
