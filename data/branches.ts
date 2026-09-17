/**
 * The four HAMKOR SAVDO branches, exactly as supplied by the business.
 *
 * `phone: null` means the business has not given a direct number for that branch
 * yet — the UI falls back to the main company number instead of inventing one.
 * Fill the field in and the branch card starts showing its own line automatically.
 */

export interface Branch {
  id: string;
  index: string;
  city: string;
  /** Full address line as the business writes it. */
  address: string;
  /** Short landmark shown as the card's headline. */
  landmark: string;
  phone: string | null;
  phoneDisplay: string | null;
  /**
   * This branch's own Instagram account, when it runs one distinct from the
   * main @hamkorsavdo.uz page. `null` when the branch has no separate page —
   * the main account already covers it.
   */
  instagram: string | null;
  instagramUrl: string | null;
  /** Egasi tomonidan berildi (2026-09-17). Filiallar orasida farq qiladi. */
  hours: string;
  /** Google Maps search query — no fabricated coordinates or place IDs. */
  mapQuery: string;
  /** Egasi yuborgan Google Maps havolasidan olingan aniq koordinata (2026-09-17). */
  lat: number;
  lng: number;
}

export const branches: Branch[] = [
  {
    id: "shahrixon-ozodbek",
    index: "01",
    city: "Shahrixon",
    address: "Shahrixon shahar, Ozodbek savdo markazi",
    landmark: "Ozodbek savdo markazi",
    // Egasi tomonidan berildi (2026-09-17): bu filialning o'z call-center raqami,
    // avval umumiy kompaniya raqami (74 342 08 80) noto'g'ri qo'yilgan edi.
    phone: "+998552030880",
    phoneDisplay: "+998 55 203 08 80",
    // Egasi tomonidan berildi (2026-09-17): bu filialning o'z Instagram sahifasi bor.
    instagram: "hamkorsavdo__shahrixon",
    instagramUrl: "https://www.instagram.com/hamkorsavdo__shahrixon",
    hours: "8:00–18:00",
    mapQuery: "Ozodbek savdo markazi, Shahrixon, Andijon",
    lat: 40.7137304,
    lng: 72.0566577,
  },
  {
    id: "shahrixon-bog",
    index: "02",
    city: "Shahrixon",
    address: "Shahrixon shahar, Shahrixon markaziy istirohat bog'i yonida",
    landmark: "Markaziy istirohat bog'i yonida",
    // Egasi tomonidan tuzatildi (2026-09-17): bu filialning to'g'ri raqami.
    phone: "+998552020101",
    phoneDisplay: "+998 55 202 01 01",
    // Alohida sahifasi yo'q — egasi asosiy @hamkorsavdo.uz ni ko'rsatdi.
    instagram: null,
    instagramUrl: null,
    hours: "8:00–18:00",
    mapQuery: "Shahrixon markaziy istirohat bog'i, Shahrixon, Andijon",
    lat: 40.710138,
    lng: 72.057308,
  },
  {
    id: "asaka-umid",
    index: "03",
    city: "Asaka",
    address: "Asaka shahar, Umid ko'chasi (Makro supermarketi 2-qavat)",
    landmark: "Makro supermarketi, 2-qavat",
    phone: "+998332323055",
    phoneDisplay: "+998 33 232 30 55",
    // Egasi tomonidan berildi (2026-09-17): "Fayzli xonadon" — shu filialning o'z sahifasi.
    instagram: "fayzlixonadon",
    instagramUrl: "https://www.instagram.com/fayzlixonadon",
    hours: "8:00–18:00",
    mapQuery: "Makro supermarket, Umid ko'chasi, Asaka, Andijon",
    lat: 40.6486808,
    lng: 72.2345559,
  },
  {
    id: "andijon-amir-temur",
    index: "04",
    city: "Andijon",
    address: "Andijon shahar, Amir Temur shoh ko'chasi, 62",
    landmark: "Amir Temur shoh ko'chasi, 62",
    // Egasi tomonidan berildi (2026-08-25). Mobil raqam: abonent qismi
    // Shahrixon-Ozodbek filialinikiga o'xshaydi, faqat kodi 74 emas, 33.
    phone: "+998333420880",
    phoneDisplay: "+998 33 342 08 80",
    // Alohida sahifasi yo'q — egasi asosiy @hamkorsavdo.uz ni ko'rsatdi.
    instagram: null,
    instagramUrl: null,
    hours: "9:00–22:00",
    mapQuery: "Amir Temur shoh ko'chasi 62, Andijon",
    lat: 40.758604,
    lng: 72.3453139,
  },
];

/**
 * Google Maps havolasi — egasi yuborgan aniq koordinatadan (2026-09-17).
 * Matn qidiruviga emas, koordinataga tayanadi, shuning uchun xarita doim
 * to'g'ri joyni ochadi (matnli qidiruv boshqa shahardagi bir xil nomli
 * joyni ham topib qo'yishi mumkin edi).
 */
export function mapsUrl(branch: Branch): string {
  return `https://www.google.com/maps/search/?api=1&query=${branch.lat},${branch.lng}`;
}
