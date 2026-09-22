import { content } from "@/lib/content";

/**
 * Verified business information for HAMKOR SAVDO.
 *
 * O'zgaradigan qiymatlar (telefon, muddat, obunachilar, havolalar) endi
 * `data/content.json` da — ularni admin panel tahrirlaydi. Bu yerda faqat
 * o'zgarmaydigan brend ma'lumotlari va hisoblangan havolalar qoladi.
 *
 * SOURCES (checked 2026-08-22): brand guidebook (colours, fonts, domain),
 * official Telegram channel t.me/hamkorsavdouz, Instagram bio @hamkorsavdo.uz,
 * branch list from the owner. 2026-09-17: installment term changed to 12 months
 * by the owner.
 *
 * `serviceArea` va `deliveryArea` ataylab alohida: bepul yetkazish faqat
 * Andijon viloyatiga tegishli, "bepul" so'zi butun mamlakatga yoyilmasin.
 */
const s = content.settings;

export const site = {
  name: "HAMKOR SAVDO",
  /** Instagram bio headline — the brand's own statement. */
  tagline: "Oilangizga ishonchli hamkor!",
  domain: "hamkorsavdo.uz",

  phone: s.phone,
  phoneDisplay: s.phoneDisplay,

  instagram: s.instagram,
  instagramUrl: `https://www.instagram.com/${s.instagram}/`,
  telegram: s.telegram,
  telegramUrl: `https://t.me/${s.telegram}`,
  telegramCustomers: s.telegramCustomers,
  telegramCustomersUrl: `https://t.me/${s.telegramCustomers}`,
  telegramBot: s.telegramBot,
  telegramBotUrl: `https://t.me/${s.telegramBot}`,

  serviceArea: "Andijon viloyati bo'ylab",
  deliveryArea: s.deliveryArea,
  freeDeliveryArea: s.freeDeliveryArea,

  facts: {
    productCount: s.productCount,
    branchCount: content.branches.length,
    installmentMonthsMax: s.installmentMonthsMax,
    freeDelivery: true,
    freeInstallation: true,
    passportAndCardOnly: true,
    instagramFollowers: s.instagramFollowers,
  },

  /**
   * Yandex Xaritadagi baho — Shahrixon (Ozodbek) filiali kartasi, 2026-09-22
   * da tekshirilgan: 4,5, 65 ta baho. O'ylab topilgan sharh o'rniga shu
   * haqiqiy raqam ko'rsatiladi; son o'sgani sari qo'lda yangilanadi.
   */
  reviews: {
    rating: 4.5,
    count: 65,
    source: "Yandex Xarita",
    readUrl: "https://yandex.uz/maps/org/165261916848/reviews/",
    writeUrl: "https://yandex.uz/maps/org/165261916848/reviews/?add-review=true",
  },

  /**
   * Yandex Xarita JS API 2.1 kaliti. Brauzerda ishlaydi, ya'ni sahifa kodida
   * baribir ochiq turadi — maxfiy emas. Himoyasi: Yandex Developer kabinetida
   * "HTTP Referer" cheklovi (hamkorsavdo.uz).
   */
  yandexMapsKey: "868c338e-c07c-4bf9-9adc-8f86a7625381",

  /** Egasi bermagan ma'lumot — bo'sh bo'lsa, UI uni ko'rsatmaydi. */
  unpublished: {
    warrantyMonths: s.warrantyMonths,
    yearFounded: s.yearFounded,
    companyStory: content.texts.companyStory || null,
  },
};
