/**
 * Canonical origin for metadata, sitemap and JSON-LD.
 * Set NEXT_PUBLIC_SITE_URL before deploying; the fallback is the domain the
 * business already prints on its materials.
 */
export const SITE_URL = (
  process.env.NEXT_PUBLIC_SITE_URL ?? "https://hamkorsavdo.uz"
).replace(/\/$/, "");

/**
 * `next.config.ts` sets `trailingSlash: true`, ya'ni har bir sahifa
 * `/filiallar/asaka-umid/` ko'rinishida joylashadi (oxirida "/"). JSON-LD
 * va canonical havolalar shu bilan bir xil bo'lishi kerak — aks holda
 * qidiruv tizimlari ikkitasini ikki xil manzil deb hisoblashi mumkin.
 * Fayl kengaytmali yo'llar (masalan "/sitemap.xml") bundan mustasno.
 */
export const absolute = (path: string) => {
  const hasExt = /\.[a-z0-9]+$/i.test(path);
  const withSlash = path === "" || path.endsWith("/") || hasExt ? path : `${path}/`;
  return `${SITE_URL}${withSlash}`;
};
