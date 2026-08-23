/**
 * The three verified business directions, exactly as listed in the Instagram
 * bio: "Tilla | Texnikalar | Mebellar". Descriptions stay generic on purpose —
 * no brands, models or assortment claims the business has not published.
 */

export type CategoryMood = "tilla" | "texnika" | "mebel";

export interface Category {
  id: CategoryMood;
  index: string;
  name: string;
  kicker: string;
  description: string;
}

export const categories: Category[] = [
  {
    id: "texnika",
    index: "01",
    name: "TEXNIKA",
    kicker: "Uy uchun zamonaviy texnika",
    description:
      "Oshxonadan mehmonxonagacha — uy yumushlarini yengillashtiradigan maishiy texnika. Kerakli mahsulotni tanlashda do'kon jamoasi yordam beradi.",
  },
  {
    id: "tilla",
    index: "02",
    name: "TILLA",
    kicker: "Zargarlik bo'limi",
    description:
      "Muhim kunlar uchun tilla taqinchoqlar. Har bir buyumni do'konning o'zida ko'rib, taqqoslab tanlaysiz.",
  },
  {
    id: "mebel",
    index: "03",
    name: "MEBEL",
    kicker: "Uy va oila uchun mebel",
    description:
      "Yotoqxonadan oshxonagacha — uyni butunlay jihozlash uchun mebel. Katta tanlov bir manzilda.",
  },
];
