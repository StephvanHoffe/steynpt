import { NextResponse, type NextRequest } from "next/server";
import { REFERRAL_COOKIE } from "@/lib/constants";
import { normalizeReferralCode } from "@/lib/loyalty";

// Persoonlijke uitnodigingslink: /r/LISA-7K2Q
export async function GET(_request: NextRequest, ctx: RouteContext<"/r/[code]">) {
  const { code: raw } = await ctx.params;
  const code = normalizeReferralCode(raw);
  if (!code) return new NextResponse(null, { status: 307, headers: { Location: "/online-coaching" } });

  const response = new NextResponse(null, {
    status: 307,
    headers: { Location: `/online-coaching?uitnodiging=${encodeURIComponent(code)}` },
  });
  response.cookies.set(REFERRAL_COOKIE, code, {
    httpOnly: true,
    sameSite: "lax",
    secure: process.env.NODE_ENV === "production",
    path: "/",
    maxAge: 60 * 60 * 24 * 60,
  });
  return response;
}
