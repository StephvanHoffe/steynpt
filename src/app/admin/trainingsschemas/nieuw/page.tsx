import type { Metadata } from "next";
import { NewPlan } from "@/components/admin/plans/NewPlan";
import { requireAdmin } from "@/lib/auth";

// Het AI-concept wordt na het versturen gemaakt (after()), binnen dit verzoek.
export const maxDuration = 300;
export const metadata: Metadata = { title: "Nieuw trainingsschema" };

export default async function Page({ searchParams }: PageProps<"/admin/trainingsschemas/nieuw">) {
  await requireAdmin("/admin/trainingsschemas/nieuw");
  return <NewPlan type="training" searchParams={await searchParams} />;
}
