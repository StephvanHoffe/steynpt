"use server";

import { and, eq } from "drizzle-orm";
import { revalidatePath } from "next/cache";
import { requireAdmin } from "../auth";
import { db, measurements, users } from "../db";
import { measurementSchema } from "../progress";
import { fieldErrorsFrom, formValues, type FormState } from "./types";

export async function addMeasurementAction(_prev: FormState, formData: FormData): Promise<FormState> {
  await requireAdmin();
  const userId = String(formData.get("userId") ?? "");
  const values = formValues(formData);
  const [member] = await db.select({ id: users.id }).from(users).where(eq(users.id, userId));
  if (!member) return { error: "Lid niet gevonden." };

  const parsed = measurementSchema.safeParse(Object.fromEntries(formData));
  if (!parsed.success) return { fieldErrors: fieldErrorsFrom(parsed.error.issues), values };
  const { measuredAt, ...data } = parsed.data;

  // Datum als 12:00 Nederlandse tijd opslaan, zodat hij in elke tijdzone op dezelfde dag valt.
  await db.insert(measurements).values({ userId, measuredAt: new Date(`${measuredAt}T12:00:00+01:00`), ...data });
  revalidatePath(`/admin/leden/${userId}`);
  revalidatePath("/account", "layout");
  return { success: "Meting opgeslagen. De klant ziet hem direct in Mijn omgeving." };
}

export async function deleteMeasurementAction(formData: FormData) {
  await requireAdmin();
  const id = Number(formData.get("id"));
  const userId = String(formData.get("userId") ?? "");
  if (!Number.isInteger(id)) return;
  await db.delete(measurements).where(and(eq(measurements.id, id), eq(measurements.userId, userId)));
  revalidatePath(`/admin/leden/${userId}`);
  revalidatePath("/account", "layout");
}
