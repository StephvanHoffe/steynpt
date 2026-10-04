// Noodgeval: tweestapsverificatie van een account uitzetten (bijvoorbeeld als Steyn zijn telefoon én herstelcodes kwijt is).
// Het account wordt overal uitgelogd en koppelt bij de volgende keer inloggen een nieuwe telefoon.
//
//   npm run auth:reset-2fa -- steyn@steynpt.nl

import { createClient } from "@libsql/client";
import { eq } from "drizzle-orm";
import { drizzle } from "drizzle-orm/libsql";
import * as s from "../src/lib/db/schema";

const email = process.argv[2]?.trim().toLowerCase();
if (!email) {
  console.error("Gebruik: npm run auth:reset-2fa -- <e-mailadres>");
  process.exit(1);
}

const client = createClient({ url: process.env.DATABASE_URL ?? "file:./data/steynpt.db", authToken: process.env.DATABASE_AUTH_TOKEN });
const db = drizzle(client, { schema: s });
const [user] = await db.select({ id: s.users.id }).from(s.users).where(eq(s.users.email, email));
if (!user) {
  console.error(`Geen account gevonden met ${email}.`);
  process.exit(1);
}
await db.transaction(async (tx) => {
  await tx.update(s.users).set({ totpSecret: null, totpEnabledAt: null, totpLastStep: null }).where(eq(s.users.id, user.id));
  await tx.delete(s.recoveryCodes).where(eq(s.recoveryCodes.userId, user.id));
  await tx.delete(s.loginChallenges).where(eq(s.loginChallenges.userId, user.id));
  await tx.delete(s.sessions).where(eq(s.sessions.userId, user.id));
});
console.log(`Tweestapsverificatie voor ${email} is uitgezet. Bij de volgende keer inloggen wordt een nieuwe telefoon gekoppeld.`);
client.close();
