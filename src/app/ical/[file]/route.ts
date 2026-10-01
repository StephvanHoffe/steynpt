import { eq, gt } from "drizzle-orm";
import { NextResponse, type NextRequest } from "next/server";
import { buildIcs } from "@/lib/agenda";
import { isValidIcalToken, toCalendarEvent } from "@/lib/agenda-server";
import { appointments, db, users } from "@/lib/db";

// iCal-abonnement voor Steyn: /ical/<token>.ics (Google Agenda, Apple Agenda, Outlook).
export async function GET(_request: NextRequest, ctx: RouteContext<"/ical/[file]">) {
  const { file } = await ctx.params;
  const token = file.replace(/\.ics$/, "");
  if (!(await isValidIcalToken(token))) return new NextResponse("Niet gevonden", { status: 404 });

  const since = new Date(Date.now() - 60 * 24 * 3600_000);
  const rows = await db
    .select({ appointment: appointments, client: { firstName: users.firstName, lastName: users.lastName, email: users.email, phone: users.phone } })
    .from(appointments)
    .innerJoin(users, eq(appointments.userId, users.id))
    .where(gt(appointments.endsAt, since));

  const body = buildIcs(
    rows.map((r) => toCalendarEvent(r.appointment, r.client, "steyn")),
    { name: "SteynPT afspraken" },
  );
  return new NextResponse(body, {
    headers: {
      "Content-Type": "text/calendar; charset=utf-8",
      "Content-Disposition": 'inline; filename="steynpt.ics"',
      "Cache-Control": "private, no-store",
      "X-Robots-Tag": "noindex",
    },
  });
}
