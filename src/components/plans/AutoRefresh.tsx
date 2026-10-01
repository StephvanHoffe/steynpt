"use client";

import { useRouter } from "next/navigation";
import { useEffect } from "react";

/** Ververst de pagina periodiek zolang er een concept gegenereerd wordt. */
export function AutoRefresh({ seconds = 4 }: { seconds?: number }) {
  const router = useRouter();
  useEffect(() => {
    const id = setInterval(() => router.refresh(), seconds * 1000);
    return () => clearInterval(id);
  }, [router, seconds]);
  return null;
}
