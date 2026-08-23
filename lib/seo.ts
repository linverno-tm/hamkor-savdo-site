/**
 * Canonical origin for metadata, sitemap and JSON-LD.
 * Set NEXT_PUBLIC_SITE_URL before deploying; the fallback is the domain the
 * business already prints on its materials.
 */
export const SITE_URL = (
  process.env.NEXT_PUBLIC_SITE_URL ?? "https://hamkorsavdo.uz"
).replace(/\/$/, "");

export const absolute = (path: string) => `${SITE_URL}${path}`;
