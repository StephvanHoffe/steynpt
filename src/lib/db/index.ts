import "server-only";
import { createClient } from "@libsql/client";
import { drizzle } from "drizzle-orm/libsql";
import * as schema from "./schema";

const url = process.env.DATABASE_URL ?? "file:./data/steynpt.db";

const globalForDb = globalThis as unknown as { steynptDb?: ReturnType<typeof createDb> };

function createDb() {
  const client = createClient({ url, authToken: process.env.DATABASE_AUTH_TOKEN });
  return drizzle(client, { schema });
}

// Hergebruik de verbinding tijdens hot reloads in development.
export const db = globalForDb.steynptDb ?? createDb();
if (process.env.NODE_ENV !== "production") globalForDb.steynptDb = db;

export * from "./schema";
