/**
 * Verified business information for HAMKOR SAVDO.
 *
 * SOURCES (all checked 2026-08-22):
 *  - Brand guidebook PDF "Guidebook HAMKOR SAVDO": corporate colour #5a3089 + white,
 *    corporate fonts TT Commons (primary) / Bebas Neue Pro (secondary), logo lockup
 *    and its usage rules, yellow #dfd01f used as the secondary key-visual colour,
 *    domain hamkorsavdo.uz, office address "Andijon, Shahrixon ko'chasi 14A".
 *  - Official Telegram channel t.me/hamkorsavdouz ("Hamkor Savdo | Rasmiy Kanali"):
 *    Andijon viloyati bo'ylab, 7000+ mahsulot, Tilla | Texnikalar | Mebellar,
 *    24 oygacha muddatli to'lov, pasport va plastik kifoya, BEPUL yetkazish/o'rnatish,
 *    phones (74) 342 08 80 and (33) 342 08 80, bot @hamkor_taklifbot.
 *  - Instagram bio @hamkorsavdo.uz: "Oilangizga ishonchli hamkor!", 41K followers.
 *  - Branch list supplied by the business owner (see data/branches.ts).
 *
 * Anything not published is `null` in `unpublished` — the UI degrades gracefully
 * and points at a phone number instead of inventing a value.
 */

export const site = {
  name: "HAMKOR SAVDO",
  /** Instagram bio headline — the brand's own statement. */
  tagline: "Oilangizga ishonchli hamkor!",
  domain: "hamkorsavdo.uz",

  /** Main company number (printed on brand materials and the Telegram channel). */
  phone: "+998743420880",
  phoneDisplay: "+998 74 342 08 80",

  instagram: "hamkorsavdo.uz",
  instagramUrl: "https://www.instagram.com/hamkorsavdo.uz/",
  telegram: "hamkorsavdouz",
  telegramUrl: "https://t.me/hamkorsavdouz",
  /** Feedback / suggestions bot listed on the official channel. */
  telegramBot: "hamkor_taklifbot",
  telegramBotUrl: "https://t.me/hamkor_taklifbot",

  /** Where the company operates, per the official Telegram channel. */
  serviceArea: "Andijon viloyati bo'ylab",

  /** Verified claims — safe to state as fact. */
  facts: {
    productCount: "7000+",
    branchCount: 4,
    installmentMonthsMax: 24,
    freeDelivery: true,
    freeInstallation: true,
    passportAndCardOnly: true,
    instagramFollowers: "41 000+",
  },

  /** NOT published by the business. Fill in and the matching UI appears by itself. */
  unpublished: {
    openingHours: null as string[] | null,
    warrantyMonths: null as number | null,
    yearFounded: null as number | null,
    companyStory: null as string | null,
  },
} as const;
