"use client";

import { useEffect, useState } from "react";

/** layout.tsx dagi THEME_SCRIPT bilan bir xil kalit. */
const KEY = "hs-theme";

/**
 * Tungi/kunduzgi rejim tugmasi (pill-switch).
 *
 * Odatiy holat — qurilma sozlamasi; <head> dagi skript uni sahifa
 * chizilishidan oldin `<html data-theme>` ga yozadi. Bosilganda tanlov
 * saqlanadi. Agar tanlov qurilmanikiga teng bo'lib qolsa — saqlanganini
 * o'chiramiz: sayt yana qurilmaga ergashadi (alohida "Avto" tugmasi kerak emas).
 *
 * Tugmachaning o'rni va belgisi CSS'da (`[data-theme="dark"] .theme-toggle-knob`),
 * shuning uchun server chizgan HTML ham darhol to'g'ri ko'rinadi.
 */
const TEXT = {
  uz: { label: "Tungi rejim", toLight: "Kunduzgi rejimga o'tish", toDark: "Tungi rejimga o'tish" },
  ru: { label: "Тёмная тема", toLight: "Светлая тема", toDark: "Тёмная тема" },
};

export function ThemeToggle({ className = "", lang = "uz" }: { className?: string; lang?: "uz" | "ru" }) {
  const t = TEXT[lang];
  const [dark, setDark] = useState<boolean | null>(null);

  useEffect(() => {
    const root = document.documentElement;
    const sync = () => setDark(root.dataset.theme === "dark");
    sync();
    const mo = new MutationObserver(sync);
    mo.observe(root, { attributes: true, attributeFilter: ["data-theme"] });
    return () => mo.disconnect();
  }, []);

  const toggle = () => {
    const next = document.documentElement.dataset.theme === "dark" ? "light" : "dark";
    document.documentElement.dataset.theme = next;
    try {
      const system = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
      if (next === system) localStorage.removeItem(KEY);
      else localStorage.setItem(KEY, next);
    } catch {
      /* Maxfiy rejim — tanlov faqat shu sahifada amal qiladi. */
    }
  };

  return (
    <button
      type="button"
      role="switch"
      aria-checked={dark ?? false}
      aria-label={t.label}
      title={dark ? t.toLight : t.toDark}
      onClick={toggle}
      className={`theme-toggle ${className}`}
    >
      <svg className="theme-toggle-icon is-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" aria-hidden="true">
        <circle cx="12" cy="12" r="4" />
        <path d="M12 2v2m0 16v2M4.9 4.9l1.4 1.4m11.4 11.4 1.4 1.4M2 12h2m16 0h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4" />
      </svg>
      <svg className="theme-toggle-icon is-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
        <path d="M20 14.5A8 8 0 0 1 9.5 4 8 8 0 1 0 20 14.5Z" />
      </svg>
      <span className="theme-toggle-knob" aria-hidden="true">
        <svg className="is-sun" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.4" strokeLinecap="round">
          <circle cx="12" cy="12" r="4" />
          <path d="M12 2v2m0 16v2M4.9 4.9l1.4 1.4m11.4 11.4 1.4 1.4M2 12h2m16 0h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4" />
        </svg>
        <svg className="is-moon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.4" strokeLinecap="round" strokeLinejoin="round">
          <path d="M20 14.5A8 8 0 0 1 9.5 4 8 8 0 1 0 20 14.5Z" />
        </svg>
      </span>
    </button>
  );
}
