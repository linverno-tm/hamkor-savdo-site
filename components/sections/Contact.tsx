import { site } from "@/data/site";
import { branches } from "@/data/branches";
import { SectionHeading } from "@/components/ui/SectionHeading";

/**
 * Scene 09 — "Qanday bog'lanaman?"
 *
 * Every official channel the business publishes, in one place: all branch
 * phone numbers, the official Telegram channel, the suggestions bot, Instagram
 * and the domain. Nothing here is buried in the footer only.
 */

function ChannelIcon({ name }: { name: "telegram" | "instagram" | "globe" | "chat" }) {
  const common = {
    width: 22,
    height: 22,
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: 1.7,
    strokeLinecap: "round" as const,
    strokeLinejoin: "round" as const,
    "aria-hidden": true,
  };
  if (name === "telegram") {
    return (
      <svg {...common}>
        <path d="M21.5 3.5 2.8 10.7c-.8.3-.8 1.4 0 1.7l4.7 1.6 1.8 5.5c.2.7 1.1.9 1.6.3l2.5-2.7 4.6 3.4c.6.4 1.4.1 1.6-.6l3-15c.2-.8-.6-1.5-1.3-1.2Z" />
        <path d="m7.5 14 11-8-8.7 9.6" />
      </svg>
    );
  }
  if (name === "instagram") {
    return (
      <svg {...common}>
        <rect x="3" y="3" width="18" height="18" rx="5" />
        <circle cx="12" cy="12" r="4" />
        <circle cx="17.2" cy="6.8" r="1.1" fill="currentColor" stroke="none" />
      </svg>
    );
  }
  if (name === "chat") {
    return (
      <svg {...common}>
        <path d="M21 12a8 8 0 1 1-3.2-6.4" />
        <path d="M21 4v5h-5" />
        <path d="M8.5 11h7M8.5 14.5h4.5" />
      </svg>
    );
  }
  return (
    <svg {...common}>
      <circle cx="12" cy="12" r="9" />
      <path d="M3 12h18M12 3a15 15 0 0 1 0 18a15 15 0 0 1 0-18" />
    </svg>
  );
}

export function Contact() {
  const channels = [
    {
      icon: "telegram" as const,
      label: "Telegram kanal",
      value: `@${site.telegram}`,
      note: "Rasmiy kanal — yangi mahsulot va aksiyalar",
      href: site.telegramUrl,
    },
    {
      icon: "instagram" as const,
      label: "Instagram",
      value: `@${site.instagram}`,
      note: `${site.facts.instagramFollowers} obunachi kuzatib boradi`,
      href: site.instagramUrl,
    },
    {
      icon: "chat" as const,
      label: "Taklif va murojaat",
      value: `@${site.telegramBot}`,
      note: "Fikr-mulohazangizni bot orqali yuboring",
      href: site.telegramBotUrl,
    },
    {
      icon: "globe" as const,
      label: "Veb-sayt",
      value: site.domain,
      note: "Rasmiy sayt",
      href: `https://${site.domain}`,
    },
  ];

  /** Unique numbers across branches, main number first. */
  const phones = [
    { label: "Umumiy raqam", phone: site.phone, display: site.phoneDisplay },
    ...branches
      .filter((b) => b.phone && b.phone !== site.phone)
      .map((b) => ({
        label: `${b.city} — ${b.landmark}`,
        phone: b.phone as string,
        display: b.phoneDisplay as string,
      })),
  ];

  return (
    <section id="aloqa" aria-labelledby="contact-title" className="bg-ground py-24 sm:py-32">
      <div className="mx-auto max-w-7xl px-5 sm:px-8">
        <SectionHeading
          id="contact-title"
          kicker="Aloqa"
          title={
            <>
              Biz bilan{" "}
              <span className="relative z-0">
                <span className="mark">bog&apos;laning</span>
              </span>
            </>
          }
          lead="Savolingiz bormi? Qo'ng'iroq qiling yoki ijtimoiy tarmoqlarda yozing — javob beramiz."
        />

        <div className="mt-14 grid gap-10 lg:grid-cols-2 lg:gap-14">
          {/* Phones */}
          <div data-reveal>
            <h3 className="numeral text-2xl tracking-[0.18em] text-ink-3">TELEFON RAQAMLAR</h3>
            <ul className="mt-6 divide-y divide-line border-y border-line">
              {phones.map((p) => (
                <li key={p.phone}>
                  <a
                    href={`tel:${p.phone}`}
                    className="group flex items-center justify-between gap-4 py-5 transition-colors hover:text-purple"
                  >
                    <span className="text-sm text-ink-2">{p.label}</span>
                    <span className="numeral shrink-0 text-2xl tracking-wide text-purple sm:text-3xl">
                      {p.display}
                    </span>
                  </a>
                </li>
              ))}
            </ul>
            <p className="mt-5 text-sm leading-relaxed text-ink-3">
              {site.unpublished.openingHours
                ? site.unpublished.openingHours.join(", ")
                : "Ish vaqti hali e'lon qilinmagan — aniqlashtirish uchun qo'ng'iroq qiling."}
            </p>
          </div>

          {/* Social + web */}
          <div data-reveal>
            <h3 className="numeral text-2xl tracking-[0.18em] text-ink-3">IJTIMOIY TARMOQLAR</h3>
            <ul className="mt-6 grid gap-3 sm:grid-cols-2">
              {channels.map((c) => (
                <li key={c.label}>
                  <a
                    href={c.href}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="card card-hover flex h-full flex-col p-5"
                  >
                    <span className="flex h-11 w-11 items-center justify-center rounded-full bg-purple-50 text-purple">
                      <ChannelIcon name={c.icon} />
                    </span>
                    <span className="mt-4 text-xs font-semibold uppercase tracking-wider text-ink-3">
                      {c.label}
                    </span>
                    <span className="mt-1 font-semibold text-ink">{c.value}</span>
                    <span className="mt-1 text-sm leading-snug text-ink-2">{c.note}</span>
                  </a>
                </li>
              ))}
            </ul>
          </div>
        </div>
      </div>
    </section>
  );
}
