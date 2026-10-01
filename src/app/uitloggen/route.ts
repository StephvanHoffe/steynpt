import { NextResponse, type NextRequest } from "next/server";
import { destroySession } from "@/lib/auth";

// Uitloggen via een gewone POST (werkt ook zonder JavaScript).
export async function POST(request: NextRequest) {
  const origin = request.headers.get("origin");
  const host = request.headers.get("x-forwarded-host") ?? request.headers.get("host");
  if (origin && host && new URL(origin).host !== host) {
    return new NextResponse("Ongeldige aanvraag", { status: 403 });
  }
  await destroySession();
  return new NextResponse(null, { status: 303, headers: { Location: "/" } });
}
