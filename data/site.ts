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
 *    24 oygacha muddatli to'lov (o'sha paytda), pasport va plastik kifoya, BEPUL
 *    yetkazish/o'rnatish, phones (74) 342 08 80 and (33) 342 08 80, bot @hamkor_taklifbot.
 *  - Instagram bio @hamkorsavdo.uz: "Oilangizga ishonchli hamkor!", 41K followers.
 *  - Branch list supplied by the business owner (see data/branches.ts).
 *  - Egasi tomonidan yangilandi (2026-09-17): muddatli to'lov endi faqat 12 oygacha
 *    beriladi — shu sabab `installmentMonthsMax` 24 dan 12 ga tushirildi.
 *
 * Anything not published is `null` in `unpublished` — the UI degrades gracefully
 * and points at a phone number instead of inventing a value.
 */

export const site = {
  name: "HAMKOR SAVDO",
  /** Instagram bio headline — the brand's own statement. */
  tagline: "Oilangizga ishonchli hamkor!",
  domain: "hamkorsavdo.uz",

  /** Main company number (2026-09-17: Andijon filiali raqami, egasi ko'rsatmasi bilan). */
  phone: "+998333420880",
  phoneDisplay: "+998 33 342 08 80",

  instagram: "hamkorsavdo.uz",
  instagramUrl: "https://www.instagram.com/hamkorsavdo.uz/",
  telegram: "hamkorsavdouz",
  telegramUrl: "https://t.me/hamkorsavdouz",
  /** Egasi tomonidan berildi (2026-09-17): mijozlar hikoyalari va sharhlari joylanadigan kanal. */
  telegramCustomers: "hamkorsavdouz_mijozlari",
  telegramCustomersUrl: "https://t.me/hamkorsavdouz_mijozlari",
  /** Feedback / suggestions bot listed on the official channel. */
  telegramBot: "hamkor_taklifbot",
  telegramBotUrl: "https://t.me/hamkor_taklifbot",

  /** Where the branches are, per the official Telegram channel. */
  serviceArea: "Andijon viloyati bo'ylab",

  /**
   * How far the company will deliver — the owner confirmed on 2026-08-25 that
   * this is not limited to the region the shops sit in.
   *
   * Kept separate from `serviceArea` on purpose. The official channel ties
   * FREE delivery to Andijon viloyati; reaching the rest of the country is a
   * different claim, and the site must not let the word "bepul" travel with it.
   *
   * [DELIVERY TERMS REQUIRED] Whether delivery outside the region is free,
   * charged, or free above some amount is not known — so the site says only
   * that it happens and points at the phone for terms.
   */
  deliveryArea: "O'zbekiston bo'ylab",
  freeDeliveryArea: "Andijon viloyati",

  /** Verified claims — safe to state as fact. */
  facts: {
    productCount: "7000+",
    branchCount: 4,
    installmentMonthsMax: 12,
    freeDelivery: true,
    freeInstallation: true,
    passportAndCardOnly: true,
    instagramFollowers: "41 000+",
  },

  /** NOT published by the business. Fill in and the matching UI appears by itself. */
  unpublished: {
    warrantyMonths: null as number | null,
    yearFounded: null as number | null,
    companyStory: null as string | null,
  },
} as const;
