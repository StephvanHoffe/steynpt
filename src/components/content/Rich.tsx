import { Fragment } from "react";
import { paragraphs, parseRich } from "@/lib/content/markup";

/** Titel uit het tekstbeheer: *sterretjes* krijgen de accentkleur (of een andere klasse), Enter wordt een regeleinde. */
export function Rich({ text, accent = "text-accent" }: { text: string; accent?: string }) {
  return (
    <>
      {parseRich(text).map((part, i) =>
        "br" in part ? (
          <br key={i} />
        ) : part.accent ? (
          <span key={i} className={accent}>
            {part.text}
          </span>
        ) : (
          <Fragment key={i}>{part.text}</Fragment>
        ),
      )}
    </>
  );
}

/** Tekst met alinea's: een lege regel begint een nieuwe alinea. */
export function Paragraphs({ text }: { text: string }) {
  return (
    <>
      {paragraphs(text).map((p, i) => (
        <p key={i}>{p}</p>
      ))}
    </>
  );
}
