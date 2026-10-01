import { ArrowRight, Briefcase, Dumbbell, Gift, Plane, Trophy } from "lucide-react";
import type { Metadata } from "next";
import Link from "next/link";
import { CtaBand } from "@/components/CtaBand";
import { DashboardPreview } from "@/components/DashboardPreview";
import { Faq } from "@/components/Faq";
import { OnlinePlans } from "@/components/OnlinePlans";
import { ReferralSteps } from "@/components/ReferralSteps";
import { ButtonLink, SectionHeading } from "@/components/ui";
import { REFERRAL } from "@/lib/referral-program";
import { resolveInvitation } from "@/lib/referral";

export const metadata: Metadata = {
  title: "Online coaching",
  description:
    "Online coaching door Steyn van Leeuwen: trainingsschema en voedingsplan op maat, wekelijkse check-ins in je eigen dashboard en persoonlijke bijsturing. Nodig een vriend uit en krijg samen korting.",
};

const audiences = [
  { icon: Dumbbell, title: "Je traint zelfstandig", text: "Je wilt een plan dat écht bij jou past en iemand die meekijkt." },
  { icon: Briefcase, title: "Je hebt een volle agenda", text: "Train wanneer het jou uitkomt, thuis, in de gym of op kantoor." },
  { icon: Plane, title: "Je bent veel onderweg", text: "Je coaching reist met je mee, waar je ook bent." },
  { icon: Trophy, title: "Je hebt een specifiek doel", text: "Wedstrijd, seizoen of persoonlijk record: we plannen ernaartoe." },
];

const steps = [
  { title: "Account aanmaken", text: "Kies je pakket en maak in twee minuten je gratis account aan." },
  { title: "Intake", text: "Steyn neemt binnen 24 uur contact op voor een intake via videocall of in de studio." },
  { title: "Jouw plan", text: "Je ontvangt je trainingsschema en voedingsplan, afgestemd op jouw doel en agenda." },
  { title: "Check-in & bijsturen", text: "Elke week check je in via je dashboard. Steyn stuurt bij en houdt je metingen bij." },
];

const faq = [
  {
    q: "Heb ik een sportschool nodig?",
    a: "Nee. Je schema wordt afgestemd op de plek waar jij traint: in de gym, thuis met beperkt materiaal of buiten.",
  },
  {
    q: "Hoe snel hoor ik iets na mijn aanmelding?",
    a: "Steyn streeft ernaar om binnen 24 uur contact met je op te nemen om de intake in te plannen. Je betaalt pas als je na de intake besluit te starten.",
  },
  {
    q: "Kan ik online coaching combineren met personal training?",
    a: "Zeker. Veel sporters combineren een paar 1-op-1 sessies in Amsterdam met online begeleiding voor de dagen ertussen. Bespreek het tijdens je intake.",
  },
  {
    q: "Hoe lang duurt een traject?",
    a: "Dat stemmen we af op jouw doel. Voor blijvend resultaat adviseert Steyn om minimaal drie maanden te rekenen.",
  },
  {
    q: "Hoe werkt de vriendenactie?",
    a: `Iedere klant heeft een persoonlijke uitnodigingslink. Een vriend die zich via die link aanmeldt krijgt ${REFERRAL.friendReward}. Start je vriend, dan krijg jij ${REFERRAL.referrerReward}.`,
  },
];

export default async function OnlineCoachingPage({ searchParams }: PageProps<"/online-coaching">) {
  const { uitnodiging } = await searchParams;
  const invitation = await resolveInvitation(uitnodiging);
  const ref = invitation?.code ?? null;

  return (
    <>
      <section className="hero-soft relative overflow-hidden">
        <div className="container-site grid items-center gap-14 py-16 lg:grid-cols-[1.2fr_1fr] lg:py-24">
          <div className="animate-rise">
            {invitation && (
              <p className="mb-6 inline-flex items-center gap-2 rounded-full bg-accent-tint px-4 py-2 text-sm font-semibold text-ink">
                <Gift className="size-4" aria-hidden="true" />
                {invitation.firstName} nodigt je uit: je krijgt {REFERRAL.friendReward}
              </p>
            )}
            <p className="eyebrow text-accent">Nieuw · Online coaching</p>
            <h1 className="display display-xl mt-5">
              Jouw coach.
              <br />
              <span className="text-accent">Altijd</span> en overal.
            </h1>
            <p className="lead mt-6 max-w-xl text-ink/75">
              De persoonlijke aanpak van SteynPT, nu ook online. Een plan op maat, wekelijkse check-ins in je eigen
              dashboard en een coach die met je meedenkt, waar je ook traint.
            </p>
            <div className="mt-8 flex flex-col gap-3 sm:flex-row">
              <ButtonLink href="#pakketten">
                Kies je pakket <ArrowRight className="size-4" aria-hidden="true" />
              </ButtonLink>
              <ButtonLink href={`/registreren${ref ? `?ref=${ref}` : ""}`} variant="outline">
                Gratis account aanmaken
              </ButtonLink>
            </div>
            <p className="mt-5 text-sm text-muted">
              Intake binnen 24 uur · Je betaalt pas na de intake
            </p>
          </div>
          <DashboardPreview />
        </div>
      </section>

      <section className="container-site py-20 lg:py-28">
        <SectionHeading
          eyebrow="Voor wie"
          title="Gemaakt voor sporters die verder willen"
          intro="Of je nu net begint of al jaren traint: online coaching geeft je structuur, kennis en een stok achter de deur."
        />
        <div className="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          {audiences.map(({ icon: Icon, title, text }) => (
            <div key={title} className="card p-6">
              <span className="grid size-11 place-items-center rounded-xl bg-accent-tint text-accent">
                <Icon className="size-5" aria-hidden="true" />
              </span>
              <h3 className="mt-5 text-lg font-semibold">{title}</h3>
              <p className="mt-2 text-[15px] text-muted">{text}</p>
            </div>
          ))}
        </div>
      </section>

      <section className="bg-surface py-20 lg:py-28">
        <div className="container-site">
          <SectionHeading eyebrow="Zo werkt het" title="In vier stappen van start" />
          <ol className="mt-12 grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            {steps.map((step, i) => (
              <li key={step.title} className="relative rounded-xl bg-paper p-7">
                <span className="display grid size-12 place-items-center rounded-full bg-ink text-2xl text-white">{i + 1}</span>
                <h3 className="display mt-6 text-2xl">{step.title}</h3>
                <p className="mt-3 text-[15px] leading-relaxed text-muted">{step.text}</p>
              </li>
            ))}
          </ol>
        </div>
      </section>

      <section id="pakketten" className="scroll-mt-28 bg-surface py-20 lg:py-28">
        <div className="container-site">
          <SectionHeading
           
            align="center"
            eyebrow="Pakketten"
            title="Kies wat bij jou past"
            intro="Alle pakketten inclusief persoonlijk dashboard, wekelijkse check-ins en je metingen in één overzicht."
          />
          <div className="mt-14">
            <OnlinePlans referral={ref} />
          </div>
          <p className="mt-8 text-center text-sm text-muted">
            Liever eerst kennismaken?{" "}
            <Link href="/contact" className="font-semibold text-ink underline decoration-accent underline-offset-4">
              Plan een gratis kennismaking
            </Link>
          </p>
        </div>
      </section>

      <section className="container-site grid gap-14 py-20 lg:grid-cols-2 lg:items-center lg:py-28">
        <div>
          <SectionHeading
            eyebrow="Vriendenactie"
            title={REFERRAL.headline}
            intro="Samen trainen is leuker en houdt je allebei scherp. Nodig een vriend uit via je persoonlijke link in Mijn omgeving."
          />
          <ButtonLink href="/vriend-uitnodigen" variant="outline" className="mt-8">
            Voorwaarden en uitleg
          </ButtonLink>
        </div>
        <ReferralSteps />
      </section>

      <section className="container-site pb-8">
        <SectionHeading eyebrow="Veelgestelde vragen" title="Goed om te weten" />
        <div className="mt-10 max-w-4xl">
          <Faq items={faq} />
        </div>
      </section>

      <CtaBand
        title="Start vandaag nog"
        text="Maak je gratis account aan, kies je pakket en Steyn neemt binnen 24 uur contact met je op."
        primary={{ href: `/registreren${ref ? `?ref=${ref}` : ""}`, label: "Account aanmaken" }}
      />
    </>
  );
}
