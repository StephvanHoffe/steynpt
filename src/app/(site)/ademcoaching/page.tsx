import { ArrowRight, Battery, Brain, Building2, Clock, Moon, Trophy, User, Users } from "lucide-react";
import type { Metadata } from "next";
import { CtaBand } from "@/components/CtaBand";
import { Faq } from "@/components/Faq";
import { PageHero } from "@/components/PageHero";
import { ButtonLink, CheckList, SectionHeading } from "@/components/ui";
import { BREATHWORK_SESSION } from "@/lib/site";

export const metadata: Metadata = {
  title: "Ademcoaching 1-op-1 en in groepsverband",
  description: `Ademcoaching door Steyn van Leeuwen in Amsterdam: een 1-op-1 ademsessie van ${BREATHWORK_SESSION.duration} voor € ${BREATHWORK_SESSION.price}, of een groepssessie op aanvraag voor bedrijven, sportteams en vriendengroepen.`,
};

const benefits = [
  { icon: Brain, title: "Rust & focus", text: "Leer je zenuwstelsel kalmeren en helder te blijven onder druk." },
  { icon: Moon, title: "Beter slapen", text: "Ademtechnieken die je helpen ontspannen en dieper herstellen." },
  { icon: Battery, title: "Meer energie", text: "Een efficiëntere ademhaling geeft meer energie gedurende de dag." },
  { icon: Trophy, title: "Sportprestatie", text: "Verbeter je uithoudingsvermogen, herstel tussen inspanningen en concentratie." },
];

const groups = [
  { icon: Building2, title: "Bedrijven", text: "Een vitaliteitssessie op kantoor of tijdens een teamdag. Werkt direct tegen werkstress." },
  { icon: Trophy, title: "Sportteams", text: "Ademtraining als onderdeel van warming-up, herstel en mentale voorbereiding." },
  { icon: Users, title: "Vrienden & groepen", text: "Samen iets nieuws ervaren, binnen in de studio of buiten in het park." },
];

const faq = [
  {
    q: "Hoe lang duurt een 1-op-1 ademsessie en wat kost het?",
    a: `Een 1-op-1 ademsessie duurt ${BREATHWORK_SESSION.duration} en kost € ${BREATHWORK_SESSION.price},-. In die tijd is er ruimte voor uitleg, oefenen en een langere begeleide ademsessie.`,
  },
  {
    q: "Hoe werkt een groepssessie?",
    a: "Groepssessies zijn op aanvraag. Neem contact op, dan stemmen we de opzet, duur en prijs af op jullie groepsgrootte en locatie. Een sessie werkt het best met kleine tot middelgrote groepen.",
  },
  {
    q: "Heb ik ervaring nodig?",
    a: "Nee. Iedere sessie start met uitleg en de oefeningen worden afgestemd op beginners én gevorderden.",
  },
  {
    q: "Is ademcoaching voor iedereen geschikt?",
    a: "Voor de meeste mensen wel. Ben je zwanger, heb je epilepsie, hart- en vaatziekten of andere medische klachten? Meld het vooraf, dan passen we de oefeningen aan of overleggen we eerst met je arts.",
  },
  {
    q: "Waar vindt een sessie plaats?",
    a: "Bij Gymbase in Amsterdam, bij jullie op kantoor of buiten. Zo lang er rustig ruimte is om te liggen of te zitten.",
  },
];

export default function AdemcoachingPage() {
  return (
    <>
      <PageHero
        eyebrow="Ademcoaching 1-op-1 en in groepsverband"
        title={
          <>
            Adem in. <span className="text-accent">Kom tot rust.</span> Presteer beter.
          </>
        }
        intro={`Je ademhaling is het krachtigste gereedschap dat je altijd bij je hebt. In een persoonlijke ademsessie van ${BREATHWORK_SESSION.duration} leer je hoe je met je adem stress verlaagt, je focus vergroot en sneller herstelt. Met je team of groep kan het ook: groepssessies zijn op aanvraag.`}
        image="/images/steyn-team-gym.jpg"
        imageAlt="Steyn met sporters in de studio"
      >
        <ButtonLink href="/contact?onderwerp=ademcoaching">
          Plan een ademsessie <ArrowRight className="size-4" aria-hidden="true" />
        </ButtonLink>
        <ButtonLink href="/contact?onderwerp=ademcoaching-groep" variant="outline">
          Groepssessie aanvragen
        </ButtonLink>
      </PageHero>

      <section className="container-site py-20 lg:py-28">
        <SectionHeading
          eyebrow="Wat het je oplevert"
          title="Kleine verandering, groot effect"
          intro="Ademcoaching sluit naadloos aan op de SteynPT-visie: een gezonde leefstijl draait niet alleen om trainen en voeding, maar ook om rust en herstel."
        />
        <div className="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          {benefits.map(({ icon: Icon, title, text }) => (
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
          <SectionHeading eyebrow="Twee vormen" title="1-op-1 of met je groep" />
          <div className="mt-12 grid gap-5 lg:grid-cols-2">
            <article className="flex flex-col rounded-xl border border-ink bg-white p-7 ring-1 ring-ink sm:p-9">
              <p className="flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.14em] text-accent">
                <User className="size-4" aria-hidden="true" /> 1-op-1
              </p>
              <h3 className="display mt-2 text-3xl">Ademsessie 1-op-1</h3>
              <p className="mt-6 flex items-baseline gap-1">
                <span className="text-lg font-semibold">€</span>
                <span className="display text-6xl">{BREATHWORK_SESSION.price}</span>
                <span className="text-lg font-semibold text-muted">,-</span>
                <span className="ml-1 text-sm text-muted">per sessie</span>
              </p>
              <p className="mt-2 flex items-center gap-2 text-sm font-medium">
                <Clock className="size-4 text-accent" aria-hidden="true" /> {BREATHWORK_SESSION.duration}
              </p>
              <div className="mt-6 flex-1">
                <CheckList items={BREATHWORK_SESSION.features} />
              </div>
              <ButtonLink href="/contact?onderwerp=ademcoaching" variant="ink" className="mt-8">
                Plan een ademsessie
              </ButtonLink>
            </article>

            <article className="flex flex-col rounded-xl border border-line bg-white p-7 sm:p-9">
              <p className="flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.14em] text-muted">
                <Users className="size-4" aria-hidden="true" /> In groepsverband
              </p>
              <h3 className="display mt-2 text-3xl">Groepssessie</h3>
              <p className="display mt-6 text-4xl">Op aanvraag</p>
              <p className="mt-2 text-sm text-muted">Opzet, duur en tarief stemmen we af op jullie groepsgrootte en locatie.</p>
              <ul className="mt-6 grid flex-1 content-start gap-4">
                {groups.map(({ icon: Icon, title, text }) => (
                  <li key={title} className="flex gap-4">
                    <Icon className="size-6 shrink-0 text-accent" aria-hidden="true" />
                    <span>
                      <span className="block font-semibold">{title}</span>
                      <span className="mt-0.5 block text-sm text-muted">{text}</span>
                    </span>
                  </li>
                ))}
              </ul>
              <ButtonLink href="/contact?onderwerp=ademcoaching-groep" variant="outline" className="mt-8">
                Vraag een groepssessie aan
              </ButtonLink>
            </article>
          </div>

          <div className="mt-16 max-w-3xl">
            <h3 className="display text-2xl">Zo ziet een sessie eruit</h3>
            <ol className="mt-8 grid gap-6 sm:grid-cols-2">
              {[
                ["Uitleg", "Wat gebeurt er in je lichaam als je ademt, en waarom werkt dit?"],
                ["Oefenen", "Basistechnieken voor ontspanning, focus en energie die je overal kunt toepassen."],
                ["Begeleide ademsessie", "Een langere sessie waarin Steyn je stap voor stap begeleidt."],
                ["Meenemen", "Je gaat naar huis met concrete oefeningen voor je dagelijks leven of sport."],
              ].map(([title, text], i) => (
                <li key={title} className="flex gap-5">
                  <span className="display grid size-11 shrink-0 place-items-center rounded-full border border-accent/50 text-xl text-accent">
                    {i + 1}
                  </span>
                  <span>
                    <span className="block text-lg font-semibold">{title}</span>
                    <span className="mt-1 block text-muted">{text}</span>
                  </span>
                </li>
              ))}
            </ol>
          </div>
        </div>
      </section>

      <section className="container-site grid gap-14 py-20 lg:grid-cols-[1fr_1.4fr] lg:py-28">
        <div>
          <SectionHeading eyebrow="Veelgestelde vragen" title="Goed om te weten" />
          <div className="mt-8">
            <CheckList
              items={[
                `1-op-1: ${BREATHWORK_SESSION.duration} voor € ${BREATHWORK_SESSION.price},-`,
                "Groepssessies op aanvraag, vanaf 3 personen",
                "Geen ervaring nodig",
                "Op locatie, in de studio of buiten",
              ]}
            />
          </div>
        </div>
        <Faq items={faq} />
      </section>

      <CtaBand
        title="Plan je ademsessie"
        text="Kom voor een persoonlijke sessie, of vertel ons over je team of groep, dan stellen we een groepssessie op maat voor."
        primary={{ href: "/contact?onderwerp=ademcoaching", label: "Plan een 1-op-1 sessie" }}
        secondary={{ href: "/contact?onderwerp=ademcoaching-groep", label: "Groepssessie aanvragen" }}
      />
    </>
  );
}
