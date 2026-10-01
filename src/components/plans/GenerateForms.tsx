import { FilePlus2, Sparkles } from "lucide-react";
import { createManualPlanAction, generatePlanAction } from "@/lib/actions/plans";
import type { PlanType } from "@/lib/db";
import { PLAN_TYPE_LABEL } from "./labels";

/** Knoppen om een AI-concept te laten maken (met optionele instructie) of zelf te beginnen. */
export function GenerateForms({ userId, type, aiEnabled, hasIntake, regenerate }: { userId: string; type: PlanType; aiEnabled: boolean; hasIntake: boolean; regenerate?: boolean }) {
  return (
    <div className="grid gap-3">
      {aiEnabled && hasIntake && (
        <form action={generatePlanAction} className="grid gap-2">
          <input type="hidden" name="userId" value={userId} />
          <input type="hidden" name="type" value={type} />
          <label className="block">
            <span className="label">{regenerate ? "Opnieuw laten maken met een instructie (optioneel)" : "Instructie voor de AI (optioneel)"}</span>
            <textarea
              name="instruction"
              rows={2}
              maxLength={1500}
              className="input min-h-0 py-2 text-sm"
              placeholder={type === "training" ? "Bijv. 'geen squats vanwege de knie, meer focus op core'" : "Bijv. 'meer warme lunches, minder zuivel'"}
            />
          </label>
          <button type="submit" className="btn btn-sm btn-primary justify-self-start">
            <Sparkles className="size-4" aria-hidden="true" /> {regenerate ? "Nieuw AI-concept" : `${PLAN_TYPE_LABEL[type]} laten maken`}
          </button>
        </form>
      )}
      <form action={createManualPlanAction}>
        <input type="hidden" name="userId" value={userId} />
        <input type="hidden" name="type" value={type} />
        <button type="submit" className="btn btn-sm btn-outline bg-white">
          <FilePlus2 className="size-4" aria-hidden="true" /> Zelf een leeg schema starten
        </button>
      </form>
      {regenerate && <p className="text-xs text-muted">Een nieuw concept vervangt het huidige concept. Een gepubliceerd schema blijft zichtbaar voor de klant tot je het nieuwe publiceert.</p>}
    </div>
  );
}
