import { demoLoginAction } from "@/lib/actions/auth";
import { DEMO_ACCOUNTS } from "@/lib/demo";

/** Balk boven de site in de demoversie, met snel wisselen tussen klant en Steyn. */
export function DemoBanner() {
  return (
    <div className="bg-accent text-white print:hidden">
      <div className="container-site flex flex-wrap items-center justify-between gap-x-6 gap-y-1.5 py-2 text-[13px]">
        <p>
          <strong className="font-semibold">Demoversie.</strong> Alle gegevens zijn voorbeelden en worden regelmatig gewist. Vul geen echte gegevens in.
        </p>
        <div className="flex items-center gap-1.5">
          <span className="mr-1 text-white/80">Bekijk als</span>
          {DEMO_ACCOUNTS.map((a) => (
            <form key={a.id} action={demoLoginAction}>
              <input type="hidden" name="account" value={a.id} />
              <button type="submit" className="rounded-md bg-white/15 px-2.5 py-1 font-semibold transition-colors hover:bg-white/25">
                {a.label}
              </button>
            </form>
          ))}
        </div>
      </div>
    </div>
  );
}
