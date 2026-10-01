import { CalendarPlus, Clock, MapPin } from "lucide-react";
import { BOOKING_RULES, formatDayLong, formatTime, getAgendaLocation, getAppointmentType } from "@/lib/agenda";
import { cancelMyAppointmentAction } from "@/lib/actions/agenda";
import type { Appointment } from "@/lib/db";

/** Komende afspraken van de klant, met agenda-download en afzeggen (tot 24 uur van tevoren). */
export function AppointmentList({ items, now = new Date(), compact = false }: { items: Appointment[]; now?: Date; compact?: boolean }) {
  if (items.length === 0) return <p className="text-sm text-muted">Je hebt geen afspraken gepland.</p>;
  return (
    <ul className="divide-y divide-line rounded-lg border border-line">
      {items.map((a) => {
        const type = getAppointmentType(a.type);
        const location = getAgendaLocation(a.location);
        const canCancel = a.startsAt.getTime() - now.getTime() >= BOOKING_RULES.cancelUntilHours * 3600_000;
        return (
          <li key={a.id} className={`flex flex-col gap-3 p-4 ${compact ? "" : "sm:flex-row sm:items-center sm:justify-between"}`}>
            <div>
              <p className="font-semibold">{type?.label ?? a.type}</p>
              <p className="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-sm text-muted">
                <span className="inline-flex items-center gap-1.5">
                  <Clock className="size-3.5" aria-hidden="true" />
                  <span className="first-letter:uppercase">
                    {formatDayLong(a.startsAt)}, {formatTime(a.startsAt)}–{formatTime(a.endsAt)}
                  </span>
                </span>
                <span className="inline-flex items-center gap-1.5">
                  <MapPin className="size-3.5" aria-hidden="true" />
                  {location?.label ?? a.location}
                </span>
              </p>
            </div>
            <div className="flex flex-wrap items-center gap-2">
              <a href={`/account/agenda/${a.id}/ics`} className="btn btn-sm btn-outline">
                <CalendarPlus className="size-4" aria-hidden="true" /> In mijn agenda
              </a>
              {canCancel ? (
                <form action={cancelMyAppointmentAction}>
                  <input type="hidden" name="id" value={a.id} />
                  <button type="submit" className="btn btn-sm text-muted hover:text-danger">
                    Afzeggen
                  </button>
                </form>
              ) : (
                <span className="text-xs text-muted">Afzeggen kan tot {BOOKING_RULES.cancelUntilHours} uur van tevoren</span>
              )}
            </div>
          </li>
        );
      })}
    </ul>
  );
}
