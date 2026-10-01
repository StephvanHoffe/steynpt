import { asc, eq } from "drizzle-orm";
import type { Metadata } from "next";
import { AppointmentForm } from "@/components/admin/AppointmentForm";
import { ADMIN_PAGE, AdminPageHeader, COACHING_LABEL } from "@/components/admin/ui";
import { formatDayLong, formatTime, getAppointmentType, isValidDay, zonedParts } from "@/lib/agenda";
import { requireAdmin } from "@/lib/auth";
import { appointments, db, users } from "@/lib/db";

export const metadata: Metadata = { title: "Nieuwe afspraak" };

export default async function NewAppointmentPage({ searchParams }: PageProps<"/admin/agenda/nieuw">) {
  await requireAdmin("/admin/agenda/nieuw");
  const sp = await searchParams;
  const today = zonedParts(new Date()).day;
  const replacesId = Number(sp.verplaats);

  const [members, [old]] = await Promise.all([
    db
      .select({ id: users.id, firstName: users.firstName, lastName: users.lastName, coachingStatus: users.coachingStatus })
      .from(users)
      .where(eq(users.role, "member"))
      .orderBy(asc(users.firstName), asc(users.lastName)),
    Number.isInteger(replacesId) && replacesId > 0
      ? db.select().from(appointments).where(eq(appointments.id, replacesId))
      : Promise.resolve([] as (typeof appointments.$inferSelect)[]),
  ]);
  const options = members.map((m) => ({ id: m.id, name: `${m.firstName} ${m.lastName}`, note: m.coachingStatus === "geen" ? "" : `coaching ${COACHING_LABEL[m.coachingStatus].toLowerCase()}` }));
  const moving = old && old.status === "gepland" ? old : undefined;

  const day = typeof sp.datum === "string" && isValidDay(sp.datum) && sp.datum >= today ? sp.datum : moving ? zonedParts(moving.startsAt).day : today;
  const time = typeof sp.tijd === "string" && /^\d{2}:\d{2}$/.test(sp.tijd) ? sp.tijd : moving ? zonedParts(moving.startsAt).time : "09:00";
  const lid = typeof sp.lid === "string" ? sp.lid : undefined;
  const locked = moving ? options.find((m) => m.id === moving.userId) : undefined;

  return (
    <div className={ADMIN_PAGE}>
      <div className="max-w-3xl">
        <AdminPageHeader
          back={{ href: `/admin/agenda?datum=${day}`, label: "Agenda" }}
          title={moving ? "Afspraak verplaatsen" : "Nieuwe afspraak"}
          description={
            moving ? (
              <>
                Nu: {getAppointmentType(moving.type)?.label} op <span className="first-letter:uppercase">{formatDayLong(moving.startsAt)}</span> om {formatTime(moving.startsAt)}. Kies
                een nieuw moment; de oude afspraak wordt dan geannuleerd.
              </>
            ) : (
              "Plan een afspraak in voor een klant, bijvoorbeeld na een telefoontje."
            )
          }
        />
        <div className="card p-5 sm:p-7">
          <AppointmentForm
            members={options}
            lockedMember={locked}
            minDay={today}
            defaults={{
              userId: moving?.userId ?? (options.some((m) => m.id === lid) ? lid : undefined),
              type: moving?.type,
              location: moving?.location,
              day,
              time,
              note: moving?.note ?? undefined,
              replaces: moving?.id,
            }}
          />
        </div>
      </div>
    </div>
  );
}
