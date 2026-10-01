import { ArrowRight, Battery, Brain, Building2, Moon, Trophy, Users, Wind } from "lucide-react";
import type { Metadata } from "next";
import { CtaBand } from "@/components/CtaBand";
import { Faq } from "@/components/Faq";
import { PageHero } from "@/components/PageHero";
import { ButtonLink, CheckList, SectionHeading } from "@/components/ui";

export const metadata: Metadata = {
  title: "Ademcoaching in groepsverband",
  description:
    "Ademcoaching in groepsverband door Steyn van Leeuwen. Voor bedrijven, sportteams en vriendengroepen: minder stress, betere focus, beter herstel en meer energie.",
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
    q: "Hoe groot kan een groep zijn?",
    a: "Een sessie werkt het best met kleine tot middelgrote groepen. Neem contact op, dan stemmen we de opzet af op jullie groepsgrootte en locatie.",
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
    a: "In één van onze studio's in Amsterdam, bij jullie op kantoor of buiten. Zo lang er rustig ruimte is om te liggen of te zitten.",
  },
];

export default function AdemcoachingPage() {
  return (
    <>
      <PageHero
        eyebrow="Ademcoaching in groepsverband"
        title={
          <>
            Adem in. <span className="text-accent">Kom tot rust.</span> Presteer beter.
          </>
        }
        intro="Je ademhaling is het krachtigste gereedschap dat je altijd bij je hebt. In een begeleide groepssessie leer je hoe je met je adem stress verlaagt, je focus vergroot en sneller herstelt."
        image="/images/steyn-team-gym.jpg"
        imageAlt="Steyn met sporters in de studio"
      >
        <ButtonLink href="/contact">
          Vraag een groepssessie aan <ArrowRight className="size-4" aria-hidden="true" />
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
        <div className="container-site grid gap-14 lg:grid-cols-2">
          <div>
            <SectionHeading eyebrow="Een sessie" title="Zo ziet een groepssessie eruit" />
            <ol className="mt-10 space-y-6">
              {[
                ["Uitleg", "Wat gebeurt er in je lichaam als je ademt, en waarom werkt dit?"],
                ["Oefenen", "Basistechnieken voor ontspanning, focus en energie die je overal kunt toepassen."],
                ["Begeleide ademsessie", "Een langere sessie waarin Steyn de groep stap voor stap begeleidt."],
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
          <div className="grid content-start gap-4">
            {groups.map(({ icon: Icon, title, text }) => (
              <div key={title} className="card-soft flex gap-5 p-6">
                <Icon className="size-7 shrink-0 text-accent" aria-hidden="true" />
                <span>
                  <span className="block text-lg font-semibold">{title}</span>
                  <span className="mt-1 block text-muted">{text}</span>
                </span>
              </div>
            ))}
            <div className="flex items-center gap-4 rounded-xl bg-accent-tint p-6 text-ink">
              <Wind className="size-8 shrink-0" aria-hidden="true" />
              <p className="font-semibold">
                Tarief op aanvraag, afhankelijk van groepsgrootte en locatie.
              </p>
            </div>
          </div>
        </div>
      </section>

      <section className="container-site grid gap-14 py-20 lg:grid-cols-[1fr_1.4fr] lg:py-28">
        <div>
          <SectionHeading eyebrow="Veelgestelde vragen" title="Goed om te weten" />
          <div className="mt-8">
            <CheckList items={["Geen ervaring nodig", "Op locatie, in de studio of buiten", "Voor teams vanaf 3 personen"]} />
          </div>
        </div>
        <Faq items={faq} />
      </section>

      <CtaBand
        title="Plan een sessie voor je groep"
        text="Vertel ons over je team of groep, dan stellen we een sessie op maat voor."
        primary={{ href: "/contact", label: "Vraag een sessie aan" }}
        secondary={{ href: "/online-coaching", label: "Bekijk online coaching" }}
      />
    </>
  );
}
