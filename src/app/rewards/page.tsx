import { ArrowRight, CalendarCheck, Flame, Gift, Megaphone, Share2, UserCheck, UserPlus } from "lucide-react";
import type { Metadata } from "next";
import { CtaBand } from "@/components/CtaBand";
import { PageHero } from "@/components/PageHero";
import { ButtonLink, CheckList, SectionHeading } from "@/components/ui";
import { activePromotions, POINTS, REWARDS, TIERS, welcomePoints } from "@/lib/loyalty";

export const metadata: Metadata = {
  title: "SteynPT Rewards & vrienden uitnodigen",
  description:
    "Spaar punten met SteynPT Rewards: voor je account, wekelijkse check-ins, streaks en vrienden die je uitnodigt. Wissel ze in voor korting, ademcoaching of een gratis PT-sessie.",
};

export default function RewardsPage() {
  const promotions = activePromotions();
  const earn = [
    { icon: UserPlus, label: "Account aanmaken", points: welcomePoints(), note: welcomePoints() > POINTS.welcome ? "Tijdelijk dubbel!" : undefined },
    { icon: UserCheck, label: "Profiel compleet (met telefoonnummer)", points: POINTS.profileComplete },
    { icon: CalendarCheck, label: "Wekelijkse check-in", points: POINTS.weeklyCheckIn, note: "Elke week" },
    { icon: Flame, label: `${POINTS.streakLength} weken op rij ingecheckt`, points: POINTS.streakBonus, note: "Steeds opnieuw" },
    { icon: Share2, label: "Vriend maakt een account via jouw link", points: POINTS.friendSignup },
    { icon: Gift, label: "Vriend start met een traject", points: POINTS.friendStarts },
    {
      icon: Megaphone,
      label: `Mijlpaal: ${POINTS.friendsMilestoneCount} vrienden gestart`,
      points: POINTS.friendsMilestone,
      note: "Bonus",
    },
  ];

  return (
    <>
      <PageHero
        eyebrow="Loyaliteitsprogramma"
        title={
          <>
            SteynPT <span className="text-volt">Rewards</span>
          </>
        }
        intro="Train, check in en neem je vrienden mee. Bij SteynPT word je beloond voor de gewoontes die écht resultaat geven. Iedere sporter met een account spaart automatisch mee."
      >
        <ButtonLink href="/registreren">
          Maak gratis account <ArrowRight className="size-4" aria-hidden="true" />
        </ButtonLink>
        <ButtonLink href="/account" variant="outline-light">
          Naar mijn punten
        </ButtonLink>
      </PageHero>

      {promotions.length > 0 && (
        <section className="bg-volt">
          <div className="container-site grid gap-4 py-10 md:grid-cols-2">
            {promotions.map((p) => (
              <div key={p.id} className="flex gap-4 rounded-[1.25rem] bg-ink p-6 text-paper">
                <Megaphone className="size-7 shrink-0 text-volt" aria-hidden="true" />
                <div>
                  <p className="text-xs font-semibold uppercase tracking-[0.14em] text-volt">Actie</p>
                  <h2 className="mt-1 text-xl font-semibold">{p.title}</h2>
                  <p className="mt-1 text-mist">{p.description}</p>
                </div>
              </div>
            ))}
          </div>
        </section>
      )}

      <section className="container-site grid gap-14 py-20 lg:grid-cols-[1fr_1.3fr] lg:py-28">
        <SectionHeading
          eyebrow="Punten verdienen"
          title="Zo spaar je punten"
          intro="Punten worden automatisch bijgeschreven in je dashboard. Je niveau wordt bepaald door al je verdiende punten; inwisselen kost je dus nooit je status."
        />
        <ul className="card divide-y divide-line">
          {earn.map(({ icon: Icon, label, points, note }) => (
            <li key={label} className="flex items-center gap-4 px-6 py-4">
              <Icon className="size-5 shrink-0 text-muted" aria-hidden="true" />
              <span className="flex-1">
                {label}
                {note && <span className="ml-2 rounded-full bg-sand px-2 py-0.5 text-xs font-semibold">{note}</span>}
              </span>
              <span className="display text-2xl">+{points}</span>
            </li>
          ))}
        </ul>
      </section>

      <section className="bg-ink py-20 text-paper lg:py-28">
        <div className="container-site">
          <SectionHeading tone="dark" eyebrow="Niveaus" title="Klim van Brons naar Platina" />
          <div className="mt-12 grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            {TIERS.map((tier, i) => (
              <div key={tier.id} className={`rounded-[1.25rem] border p-7 ${i === TIERS.length - 1 ? "border-volt bg-volt text-ink" : "card-dark"}`}>
                <p className={`text-xs font-semibold uppercase tracking-[0.14em] ${i === TIERS.length - 1 ? "text-ink/70" : "text-mist"}`}>
                  {tier.minPoints === 0 ? "Start" : `Vanaf ${tier.minPoints} punten`}
                </p>
                <h3 className="display mt-2 text-4xl">{tier.name}</h3>
                <ul className="mt-6 space-y-2 text-sm">
                  {tier.perks.map((perk) => (
                    <li key={perk} className="flex gap-2">
                      <span className={`mt-2 size-1.5 shrink-0 rotate-45 ${i === TIERS.length - 1 ? "bg-ink" : "bg-volt"}`} />
                      {perk}
                    </li>
                  ))}
                </ul>
              </div>
            ))}
          </div>
        </div>
      </section>

      <section className="container-site py-20 lg:py-28">
        <SectionHeading eyebrow="Beloningen" title="Wissel je punten in" intro="Kies je beloning in je dashboard. Steyn neemt contact met je op om hem in te plannen of toe te passen." />
        <div className="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
          {REWARDS.map((r) => (
            <div key={r.id} className="card flex flex-col p-6">
              <Gift className="size-6" aria-hidden="true" />
              <h3 className="mt-5 text-lg font-semibold">{r.title}</h3>
              <p className="mt-1 flex-1 text-sm text-muted">{r.description}</p>
              <p className="display mt-5 text-3xl">
                {r.cost} <span className="text-base text-muted">punten</span>
              </p>
            </div>
          ))}
        </div>
      </section>

      <section id="vrienden" className="scroll-mt-28 bg-sand py-20 lg:py-28">
        <div className="container-site grid gap-14 lg:grid-cols-2 lg:items-center">
          <SectionHeading
            eyebrow="Vrienden uitnodigen"
            title={
              <>
                Samen trainen is <span className="marker">leuker</span>
              </>
            }
            intro="In je dashboard vind je je persoonlijke uitnodigingslink. Deel hem via WhatsApp, e-mail of social media. Je vriend krijgt extra welkomstpunten en jij wordt beloond zodra je vriend zich aanmeldt en start."
          />
          <ol className="grid gap-4">
            {[
              ["Deel je link", "Direct vanuit je dashboard, met één tik via WhatsApp."],
              ["Vriend meldt zich aan", `+${POINTS.friendSignup} voor jou, +${POINTS.invitedBonus} extra voor je vriend.`],
              ["Vriend start een traject", `+${POINTS.friendStarts} voor jou. Bij ${POINTS.friendsMilestoneCount} vrienden nog eens +${POINTS.friendsMilestone}.`],
            ].map(([title, text], i) => (
              <li key={title} className="flex gap-5 rounded-[1.25rem] bg-paper p-6">
                <span className="display grid size-12 shrink-0 place-items-center rounded-full bg-ink text-2xl text-volt">{i + 1}</span>
                <span>
                  <span className="block text-lg font-semibold">{title}</span>
                  <span className="mt-1 block text-muted">{text}</span>
                </span>
              </li>
            ))}
          </ol>
        </div>
      </section>

      <section id="voorwaarden" className="container-site scroll-mt-28 py-20">
        <div className="max-w-3xl">
          <h2 className="display display-sm">Voorwaarden SteynPT Rewards</h2>
          <div className="mt-6 text-sm text-muted">
            <CheckList
              items={[
                "Punten zijn persoonlijk, niet overdraagbaar en niet inwisselbaar voor geld.",
                "Je verdient één keer per kalenderweek punten voor een check-in.",
                "Punten voor een vriend die start worden toegekend zodra diens traject actief is.",
                "Uitnodigen van jezelf of het aanmaken van meerdere accounts is niet toegestaan; misbruik kan leiden tot het vervallen van punten.",
                "Beloningen worden in overleg ingepland of verrekend. SteynPT kan het programma, de acties en de beloningen aanpassen; reeds gespaarde punten blijven geldig.",
              ]}
            />
          </div>
        </div>
      </section>

      <CtaBand
        title="Begin vandaag met sparen"
        text={`Maak je gratis account aan en ontvang direct ${welcomePoints()} welkomstpunten.`}
        primary={{ href: "/registreren", label: "Account aanmaken" }}
        secondary={{ href: "/online-coaching", label: "Bekijk online coaching" }}
      />
    </>
  );
}
