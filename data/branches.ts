import { content } from "@/lib/content";

/**
 * Filiallar — `data/content.json` dan (admin panel tahrirlaydi).
 *
 * `phone: null` — filialning o'z raqami berilmagan, UI umumiy raqamni
 * ko'rsatadi. `closed` — "vaqtincha yopiq" belgisi.
 */
export interface Branch {
  id: string;
  index: string;
  city: string;
  address: string;
  landmark: string;
  phone: string | null;
  phoneDisplay: string | null;
  instagram: string | null;
  instagramUrl: string | null;
  hours: string;
  mapQuery: string;
  lat: number;
  lng: number;
  closed: boolean;
  closedNote: string;
}

export const branches: Branch[] = content.branches.map((b, i) => ({
  ...b,
  index: String(i + 1).padStart(2, "0"),
  instagramUrl: b.instagram ? `https://www.instagram.com/${b.instagram}` : null,
}));

/**
 * Google Maps havolasi — aniq koordinatadan. Matnli qidiruv boshqa shahardagi
 * bir xil nomli joyni topib qo'yishi mumkin edi.
 */
export function mapsUrl(branch: Branch): string {
  return `https://www.google.com/maps/search/?api=1&query=${branch.lat},${branch.lng}`;
}

/**
 * Shaharlar ro'yxati matn ko'rinishida: "Shahrixon (2 ta), Asaka va Andijon".
 *
 * Bir nechta joyda shu ro'yxat qo'lda yozilgan edi. Admin panel filial qo'shsa
 * yoki o'chirsa, o'sha matnlar jimgina eskirib qolar edi — endi ular ham
 * `content.json` dan hisoblanadi. Bir shaharda bir nechta filial bo'lsa,
 * soni qavs ichida ko'rsatiladi.
 */
export function cityList(options?: { counts?: boolean }): string {
  const showCounts = options?.counts !== false;
  const counts = new Map<string, number>();
  for (const b of branches) counts.set(b.city, (counts.get(b.city) ?? 0) + 1);

  const names = Array.from(counts, ([city, n]) =>
    showCounts && n > 1 ? `${city} (${n} ta)` : city,
  );
  if (names.length <= 1) return names.join("");
  return `${names.slice(0, -1).join(", ")} va ${names[names.length - 1]}`;
}
