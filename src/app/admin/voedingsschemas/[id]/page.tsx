import type { Metadata } from "next";
import { PlanDetail } from "@/components/admin/plans/PlanDetail";
import { requireAdmin } from "@/lib/auth";

export const metadata: Metadata = { title: "Voedingsschema" };
// Een nieuw AI-concept vanaf deze pagina wordt na het versturen gemaakt (after()).
export const maxDuration = 300;

export default async function Page({ params }: PageProps<"/admin/voedingsschemas/[id]">) {
  await requireAdmin();
  return <PlanDetail type="voeding" id={(await params).id} />;
}
