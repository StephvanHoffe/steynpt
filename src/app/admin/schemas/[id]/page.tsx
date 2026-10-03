import { eq } from "drizzle-orm";
import { notFound, redirect } from "next/navigation";
import { requireAdmin } from "@/lib/auth";
import { db, plans } from "@/lib/db";
import { planHref } from "@/lib/plans/sections";

// Oude adressen van een schema verwijzen door naar het onderdeel van het juiste type.
export default async function Page({ params }: PageProps<"/admin/schemas/[id]">) {
  await requireAdmin();
  const id = Number((await params).id);
  if (!Number.isInteger(id)) notFound();
  const [plan] = await db.select({ type: plans.type }).from(plans).where(eq(plans.id, id));
  if (!plan) notFound();
  redirect(planHref(plan.type, id));
}
