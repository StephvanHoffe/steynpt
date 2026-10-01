import { asc, eq } from "drizzle-orm";
import { ArrowLeft } from "lucide-react";
import type { Metadata } from "next";
import Link from "next/link";
import { ProgressOverview } from "@/components/progress/ProgressOverview";
import { requireUser } from "@/lib/auth";
import { db, measurements } from "@/lib/db";

export const metadata: Metadata = { title: "Mijn voortgang" };

export default async function ProgressPage() {
  const user = await requireUser("/account/voortgang");
  const rows = await db.select().from(measurements).where(eq(measurements.userId, user.id)).orderBy(asc(measurements.measuredAt));

  return (
    <div className="container-site max-w-5xl py-10 lg:py-14">
      <p className="eyebrow text-accent">Mijn omgeving</p>
      <h1 className="display display-lg mt-3">Mijn voortgang</h1>
      <p className="lead mt-3 text-muted">Alle metingen die Steyn met je heeft gedaan, met per onderdeel het verloop sinds je eerste meting.</p>
      <div className="mt-10">
        {rows.length === 0 ? (
          <p className="rounded-lg bg-surface p-5 text-sm">
            Er zijn nog geen metingen.{" "}
            <Link href="/account/agenda?type=meting" className="font-semibold underline decoration-accent underline-offset-4">
              Plan een meting
            </Link>
          </p>
        ) : (
          <ProgressOverview rows={rows} table />
        )}
      </div>
      <Link href="/account" className="mt-10 inline-flex items-center gap-1.5 text-sm font-semibold text-muted hover:text-ink">
        <ArrowLeft className="size-4" aria-hidden="true" /> Terug naar mijn omgeving
      </Link>
    </div>
  );
}
