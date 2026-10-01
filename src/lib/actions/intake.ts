"use server";

import { eq } from "drizzle-orm";
import { redirect } from "next/navigation";
import { after } from "next/server";
import { requireUser } from "../auth";
import { db, intakes, users } from "../db";
import { intakeFromFormData, intakeSchema } from "../intake";
import { createPlanJob, generatePlan, mayAutoGenerate } from "../plans/generate";
import { fieldErrorsFrom, formValues, type FormState } from "./types";

export async function saveIntakeAction(_prev: FormState, formData: FormData): Promise<FormState> {
  const user = await requireUser("/account/intake");
  const values = formValues(formData);
  // Meerkeuzevelden als komma-lijst bewaren, zodat het formulier ze na een fout opnieuw aanvinkt.
  values.wants = formData.getAll("wants").join(",");
  values.allergies = formData.getAll("allergies").join(",");

  const parsed = intakeSchema.safeParse(intakeFromFormData(formData));
  const consent = formData.get("consent") === "on";
  if (!parsed.success || !consent) {
    const fieldErrors = parsed.success ? {} : fieldErrorsFrom(parsed.error.issues);
    if (!consent) fieldErrors.consent = "Geef toestemming om je gegevens te gebruiken voor je schema";
    return { error: "Controleer de gemarkeerde velden.", fieldErrors, values };
  }
  const data = parsed.data;

  await db
    .insert(intakes)
    .values({ userId: user.id, data })
    .onConflictDoUpdate({ target: intakes.userId, set: { data, updatedAt: new Date() } });
  await db.update(users).set({ goal: data.goal }).where(eq(users.id, user.id));

  let generating = false;
  if (await mayAutoGenerate(user)) {
    const ids: number[] = [];
    for (const type of data.wants) ids.push(await createPlanJob(user.id, type));
    after(() => Promise.all(ids.map((id) => generatePlan(id))));
    generating = true;
  }

  redirect(`/account?intake=${generating ? "gestart" : "opgeslagen"}`);
}
