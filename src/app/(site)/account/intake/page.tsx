import { eq } from "drizzle-orm";
import type { Metadata } from "next";
import { IntakeForm } from "@/components/account/IntakeForm";
import { requireUser } from "@/lib/auth";
import { db, intakes } from "@/lib/db";
import { intakeSchema } from "@/lib/intake";

export const metadata: Metadata = { title: "Mijn intake" };
// Na het opslaan kan direct het AI-concept gemaakt worden (via after()).
export const maxDuration = 300;

export default async function IntakePage() {
  const user = await requireUser("/account/intake");
  const [row] = await db.select().from(intakes).where(eq(intakes.userId, user.id));
  const initial = intakeSchema.safeParse(row?.data);

  return (
    <div className="container-site max-w-3xl py-10 lg:py-14">
      <p className="eyebrow text-accent">Mijn omgeving</p>
      <h1 className="display display-lg mt-3">{row ? "Intake bijwerken" : "Jouw intake"}</h1>
      <p className="lead mt-4 text-muted">
        Vertel ons over je doel, je training en je voeding. Op basis hiervan maken we je persoonlijke trainings- en/of
        voedingsschema. Steyn controleert het altijd voordat je het te zien krijgt.
      </p>
      <div className="mt-10">
        <IntakeForm initial={initial.success ? initial.data : undefined} defaultGoal={user.goal} />
      </div>
    </div>
  );
}
