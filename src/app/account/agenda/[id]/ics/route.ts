import { and, eq } from "drizzle-orm";
import { NextResponse, type NextRequest } from "next/server";
import { buildIcs } from "@/lib/agenda";
import { toCalendarEvent } from "@/lib/agenda-server";
import { getCurrentUser } from "@/lib/auth";
import { appointments, db } from "@/lib/db";

// Losse afspraak als .ics, zodat de klant hem in de eigen agenda kan zetten.
export async function GET(_request: NextRequest, ctx: RouteContext<"/account/agenda/[id]/ics">) {
  const user = await getCurrentUser();
  if (!user) return new NextResponse("Log eerst in", { status: 401 });
  const { id } = await ctx.params;
  const [appointment] = await db
    .select()
    .from(appointments)
    .where(and(eq(appointments.id, Number(id)), eq(appointments.userId, user.id)));
  if (!appointment) return new NextResponse("Niet gevonden", { status: 404 });

  return new NextResponse(buildIcs([toCalendarEvent(appointment, user, "klant")], { name: "SteynPT" }), {
    headers: {
      "Content-Type": "text/calendar; charset=utf-8",
      "Content-Disposition": `attachment; filename="steynpt-afspraak-${appointment.id}.ics"`,
      "Cache-Control": "private, no-store",
    },
  });
}
