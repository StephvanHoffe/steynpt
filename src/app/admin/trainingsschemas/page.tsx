import type { Metadata } from "next";
import { PlanOverview } from "@/components/admin/plans/PlanOverview";
import { requireAdmin } from "@/lib/auth";

export const metadata: Metadata = { title: "Trainingsschema's" };

export default async function Page({ searchParams }: PageProps<"/admin/trainingsschemas">) {
  await requireAdmin("/admin/trainingsschemas");
  return <PlanOverview type="training" searchParams={await searchParams} />;
}
