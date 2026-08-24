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
  /**
   * A real photograph of that department in one of the branches. Cropped by
   * `tools/bosh-rasmlar.py`, which also records where each frame came from.
   * `alt` says what is in the frame, not what the section is called — a screen
   * reader user already heard the heading.
   */
  photo: { src: string; srcSmall: string; alt: string };
}

export const categories: Category[] = [
  {
    id: "texnika",
    index: "01",
    name: "TEXNIKA",
    kicker: "Uy uchun zamonaviy texnika",
    description:
      "Oshxonadan mehmonxonagacha — uy yumushlarini yengillashtiradigan maishiy texnika. Kerakli mahsulotni tanlashda do'kon jamoasi yordam beradi.",
    photo: {
      src: "/yonalishlar/texnika-900.webp",
      srcSmall: "/yonalishlar/texnika-480.webp",
      alt: "Maishiy texnika bo'limi — muzlatgich, kir yuvish mashinasi va oshxona texnikasi qatorlari",
    },
  },
  {
    id: "tilla",
    index: "02",
    name: "TILLA",
    kicker: "Zargarlik bo'limi",
    description:
      "Muhim kunlar uchun tilla taqinchoqlar. Har bir buyumni do'konning o'zida ko'rib, taqqoslab tanlaysiz.",
    photo: {
      src: "/yonalishlar/tilla-900.webp",
      srcSmall: "/yonalishlar/tilla-480.webp",
      alt: "Zargarlik bo'limi — shisha peshtaxtada taqinchoqlar",
    },
  },
  {
    id: "mebel",
    index: "03",
    name: "MEBEL",
    kicker: "Uy va oila uchun mebel",
    description:
      "Yotoqxonadan oshxonagacha — uyni butunlay jihozlash uchun mebel. Katta tanlov bir manzilda.",
    photo: {
      src: "/yonalishlar/mebel-900.webp",
      srcSmall: "/yonalishlar/mebel-480.webp",
      alt: "Mebel bo'limi — oshxona stoli va o'rindiqlar to'plami",
    },
  },
];
