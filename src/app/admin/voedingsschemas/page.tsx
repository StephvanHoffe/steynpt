import type { Metadata } from "next";
import { PlanOverview } from "@/components/admin/plans/PlanOverview";
import { requireAdmin } from "@/lib/auth";

export const metadata: Metadata = { title: "Voedingsschema's" };

export default async function Page({ searchParams }: PageProps<"/admin/voedingsschemas">) {
  await requireAdmin("/admin/voedingsschemas");
  return <PlanOverview type="voeding" searchParams={await searchParams} />;
}
