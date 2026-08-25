"use client";

import { useEffect } from "react";
import { groupDigits } from "@/lib/format";

/**
 * The entire motion system, in place of an animation library.
 *
 * One IntersectionObserver hides each `[data-reveal]` block that is still below
 * the fold, then transitions it in on entry; the movement itself is CSS (see
 * globals.css). A second pass counts numbers up on `[data-count]`. Together
 * that is roughly a kilobyte of JavaScript instead of ~80 KB of GSAP + Lenis,
 * which matters on the mobile connections this site is built for.
 *
 * Nothing is hidden by the server render, and the `.reveal` class is only ever
 * added here — so with JS disabled, on an old browser, or under
 * `prefers-reduced-motion`, the page is simply fully visible.
 */
export function Reveal() {
  // Progressive enhancement for the <details> mobile menu, which already opens,
  // closes and announces itself without JS. This only adds the conveniences a
  // native disclosure lacks: Escape to close, closing after a jump link, and
  // locking the page behind the panel.
  useEffect(() => {
    const menu = document.getElementById("mobile-menu") as HTMLDetailsElement | null;
    if (!menu) return;

    const close = () => {
      menu.removeAttribute("open");
      // Scroll qulfini shu yerda ochamiz, `toggle` hodisasini kutmasdan:
      // u navbatga tushadi va brauzer anchor'ga sakraydigan paytda
      // `overflow: hidden` hali kuchda bo'lishi mumkin.
      document.documentElement.style.overflow = "";
    };
    const onToggle = () => {
      document.documentElement.style.overflow = menu.open ? "hidden" : "";
    };
    const onKey = (e: KeyboardEvent) => {
      if (e.key === "Escape" && menu.open) {
        close();
        menu.querySelector("summary")?.focus();
      }
    };
    /* Menyudagi HAR QANDAY havola panelni yopadi.
       Avval bu shart `a[href^="#"]` edi va hech qachon bajarilmasdi:
       navigatsiya havolalari `/#filiallar` ko'rinishida, ya'ni `/` bilan
       boshlanadi. Natijada telefonda odam menyudan bo'lim tanlasa, panel
       ochiq qolib, sahifa qulflanib turaverardi — bosdi, hech narsa
       bo'lmadi. Telefon va Telegram havolalarida ham yopilgani to'g'ri. */
    const onClick = (e: MouseEvent) => {
      if ((e.target as HTMLElement).closest("a[href]")) close();
    };

    menu.addEventListener("toggle", onToggle);
    menu.addEventListener("click", onClick);
    window.addEventListener("keydown", onKey);
    return () => {
      menu.removeEventListener("toggle", onToggle);
      menu.removeEventListener("click", onClick);
      window.removeEventListener("keydown", onKey);
      document.documentElement.style.overflow = "";
    };
  }, []);

  useEffect(() => {
    const reduced = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    const blocks = Array.from(document.querySelectorAll<HTMLElement>("[data-reveal]"));

    const countUp = (el: HTMLElement) => {
      const target = Number(el.dataset.count);
      if (!Number.isFinite(target)) return;
      const suffix = el.dataset.countSuffix ?? "";
      const duration = 900;
      const start = performance.now();
      const step = (now: number) => {
        const t = Math.min(1, (now - start) / duration);
        const eased = 1 - Math.pow(1 - t, 3);
        el.textContent = groupDigits(Math.round(target * eased)) + suffix;
        if (t < 1) requestAnimationFrame(step);
      };
      requestAnimationFrame(step);
    };

    // Counters are server-rendered at their final value, so they are correct
    // with JS off. Reduced motion leaves every number and block exactly as-is.
    if (reduced) return;

    const observer = new IntersectionObserver(
      (entries) => {
        for (const entry of entries) {
          if (!entry.isIntersecting) continue;
          const el = entry.target as HTMLElement;
          el.classList.add("is-in");
          el.querySelectorAll<HTMLElement>("[data-count]").forEach(countUp);
          observer.unobserve(el);
        }
      },
      { rootMargin: "0px 0px -10% 0px", threshold: 0.1 },
    );

    // Read phase — measure everything before touching the DOM, so the whole
    // pass costs one layout instead of one per block.
    const fold = window.innerHeight * 0.9;
    const pending: HTMLElement[] = [];
    const immediate: HTMLElement[] = [];
    for (const el of blocks) {
      (el.getBoundingClientRect().top < fold ? immediate : pending).push(el);
    }

    // Write phase.
    for (const el of blocks) {
      Array.from(el.children).forEach((child, i) => {
        (child as HTMLElement).style.setProperty("--i", String(i));
      });
    }
    // Anything already on screen at load stays put — effects run after the
    // first paint, so hiding it now would be a visible flash.
    for (const el of immediate) {
      el.querySelectorAll<HTMLElement>("[data-count]").forEach(countUp);
    }
    for (const el of pending) {
      el.classList.add("reveal");
    }

    // One forced reflow commits the hidden state untransitioned; only then are
    // the transitions armed, so nothing fades out on the way in.
    void document.body.offsetHeight;
    for (const el of pending) {
      el.classList.add("is-armed");
      observer.observe(el);
    }

    return () => observer.disconnect();
  }, []);

  return null;
}
