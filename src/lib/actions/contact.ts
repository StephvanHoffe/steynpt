"use server";

import { z } from "zod";
import { contactRequests, db } from "../db";
import { INTERESTS } from "../site";
import { fieldErrorsFrom, formValues, type FormState } from "./types";

const contactSchema = z.object({
  name: z.string().trim().min(2, "Vul je naam in").max(120),
  email: z.email("Vul een geldig e-mailadres in").trim().toLowerCase().max(200),
  phone: z.string().trim().max(30).optional(),
  interest: z.enum(INTERESTS.map((i) => i.id) as [string, ...string[]], "Kies waar je interesse in hebt"),
  message: z.string().trim().max(2000).optional(),
});

export async function contactAction(_prev: FormState, formData: FormData): Promise<FormState> {
  // Honeypot-veld: echte bezoekers zien dit veld niet.
  if (String(formData.get("website") ?? "").length > 0) return { success: "Bedankt!" };

  const values = formValues(formData);
  const parsed = contactSchema.safeParse(Object.fromEntries(formData));
  if (!parsed.success) return { fieldErrors: fieldErrorsFrom(parsed.error.issues), values };

  await db.insert(contactRequests).values({
    ...parsed.data,
    phone: parsed.data.phone || null,
    message: parsed.data.message || null,
  });
  return {
    success: "Bedankt voor je aanvraag! Steyn streeft ernaar om binnen 24 uur contact met je op te nemen. Lukt dat niet telefonisch, check dan je mailbox.",
  };
}
