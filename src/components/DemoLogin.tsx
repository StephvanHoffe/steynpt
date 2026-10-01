import { ArrowRight } from "lucide-react";
import { demoLoginAction } from "@/lib/actions/auth";
import { DEMO_ACCOUNTS } from "@/lib/demo";

/** Inloggen met één klik in de demoversie. */
export function DemoLogin({ missing }: { missing?: boolean }) {
  return (
    <section aria-labelledby="demo-login" className="rounded-xl border border-accent/30 bg-accent-tint p-5">
      <h2 id="demo-login" className="font-semibold">
        Demo: kijk direct rond
      </h2>
      <p className="mt-1 text-sm text-muted">Log in met één klik als voorbeeldklant of als Steyn. Je kunt ook zelf een account aanmaken.</p>
      {missing && <p className="mt-2 text-sm font-medium text-danger">Dit demo-account bestaat niet meer. Na een herstart van de demo is het er weer.</p>}
      <div className="mt-4 grid gap-2 sm:grid-cols-2">
        {DEMO_ACCOUNTS.map((a) => (
          <form key={a.id} action={demoLoginAction}>
            <input type="hidden" name="account" value={a.id} />
            <button type="submit" className="group grid h-full w-full gap-0.5 rounded-lg border border-line bg-white p-4 text-left transition-colors hover:border-ink">
              <span className="flex items-center justify-between gap-2 font-semibold">
                {a.role}: {a.name}
                <ArrowRight className="size-4 shrink-0 transition-transform group-hover:translate-x-0.5" aria-hidden="true" />
              </span>
              <span className="text-sm text-muted">{a.description}</span>
            </button>
          </form>
        ))}
      </div>
    </section>
  );
}
