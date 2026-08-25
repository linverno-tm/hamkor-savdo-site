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
  /** Google Maps search query — no fabricated coordinates or place IDs. */
  mapQuery: string;
}

export const branches: Branch[] = [
  {
    id: "shahrixon-ozodbek",
    index: "01",
    city: "Shahrixon",
    address: "Shahrixon shahar, Ozodbek savdo markazi",
    landmark: "Ozodbek savdo markazi",
    phone: "+998743420880",
    phoneDisplay: "+998 74 342 08 80",
    mapQuery: "Ozodbek savdo markazi, Shahrixon, Andijon",
  },
  {
    id: "shahrixon-bog",
    index: "02",
    city: "Shahrixon",
    address: "Shahrixon shahar, Shahrixon markaziy istirohat bog'i yonida",
    landmark: "Markaziy istirohat bog'i yonida",
    phone: "+998743423240",
    phoneDisplay: "+998 74 342 32 40",
    mapQuery: "Shahrixon markaziy istirohat bog'i, Shahrixon, Andijon",
  },
  {
    id: "asaka-umid",
    index: "03",
    city: "Asaka",
    address: "Asaka shahar, Umid ko'chasi (Makro supermarketi 2-qavat)",
    landmark: "Makro supermarketi, 2-qavat",
    phone: "+998332323055",
    phoneDisplay: "+998 33 232 30 55",
    mapQuery: "Makro supermarket, Umid ko'chasi, Asaka, Andijon",
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
    mapQuery: "Amir Temur shoh ko'chasi 62, Andijon",
  },
];

/** Google Maps directions link built from the address — no invented place IDs. */
export function mapsUrl(branch: Branch): string {
  return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(branch.mapQuery)}`;
}
