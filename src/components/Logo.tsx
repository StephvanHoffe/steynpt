import Image from "next/image";

type Props = { variant?: "light" | "dark"; className?: string; priority?: boolean };

/** Het originele SteynPT-logo. "light" = wit logo voor donkere achtergronden. */
export function Logo({ variant = "dark", className = "h-12 w-auto", priority }: Props) {
  return (
    <Image
      src={variant === "light" ? "/brand/steynpt-logo-white.png" : "/brand/steynpt-logo-black.png"}
      alt="SteynPT"
      width={500}
      height={336}
      className={className}
      priority={priority}
    />
  );
}

export function LogoMark({ variant = "dark", className = "h-10 w-auto" }: Props) {
  return (
    <Image
      src={variant === "light" ? "/brand/steynpt-mark-white.png" : "/brand/steynpt-mark-black.png"}
      alt=""
      width={112}
      height={215}
      className={className}
    />
  );
}
