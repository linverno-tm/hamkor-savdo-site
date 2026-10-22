import { existsSync, readFileSync } from "node:fs";
import { join } from "node:path";

/**
 * Telegram kanalidan olingan takliflar (tools/kanal-katalog.mjs, prebuild).
 * Fayl git'da yo'q — build'da yasaladi. Yo'q yoki buzilgan bo'lsa, bo'sh
 * ro'yxat: bosh sahifadagi bo'lim shunchaki ko'rinmaydi.
 */
export interface KanalItem {
  id: number;
  url: string;
  /** YYYY-MM-DD */
  date: string;
  title: string;
  /** Postdagi narx qatorlari, o'zgarishsiz (13+ oylik bo'laklar olib tashlangan). */
  prices: string[];
  body: string;
  /** `/kanal/<id>` — `-480.webp`, `-960.webp` qo'shiladi; bo'sh bo'lishi mumkin. */
  image: string;
}

export function kanalItems(): KanalItem[] {
  const file = join(process.cwd(), "data", "kanal.json");
  if (!existsSync(file)) return [];
  try {
    const data = JSON.parse(readFileSync(file, "utf8"));
    return Array.isArray(data.items) ? data.items : [];
  } catch {
    return [];
  }
}

/** Rasmiy kanaldagi mijoz lavhalari (tools/kanal-lavhalar.mjs, prebuild). */
export interface Lavha {
  id: number;
  url: string;
  date: string;
  title: string;
  /** `/lavha/<id>` — video muqovasi, `-480.webp` / `-960.webp`. */
  image: string;
}

export function lavhalar(): Lavha[] {
  const file = join(process.cwd(), "data", "lavhalar.json");
  if (!existsSync(file)) return [];
  try {
    const data = JSON.parse(readFileSync(file, "utf8"));
    return Array.isArray(data.items) ? data.items : [];
  } catch {
    return [];
  }
}
