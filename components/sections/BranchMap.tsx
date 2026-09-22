"use client";

import { useEffect, useRef, useState } from "react";

export interface MapBranch {
  id: string;
  /** "01", "02" ... — xaritadagi belgida. */
  index: string;
  title: string;
  address: string;
  hours: string;
  lat: number;
  lng: number;
  href: string;
  phone: string;
  phoneDisplay: string;
}

/* Yandex Xarita JS API 2.1 — faqat ishlatiladigan qismi uchun tip. */
interface YPlacemark {
  properties: { set: (key: string, value: unknown) => void };
  options: { set: (key: string, value: unknown) => void };
  events: { add: (type: string, cb: () => void) => void };
}
interface YMap {
  geoObjects: { add: (o: YPlacemark) => void; getBounds: () => number[][] | null };
  setBounds: (b: number[][], o?: object) => void;
  setCenter: (c: number[], z?: number, o?: object) => Promise<void> | void;
  behaviors: { disable: (n: string) => void };
  controls: { add: (name: string, options?: object) => void };
  destroy: () => void;
}
interface YMaps {
  ready: (cb: () => void) => void;
  Map: new (el: HTMLElement, state: object, options?: object) => YMap;
  Placemark: new (coords: number[], props: object, options?: object) => YPlacemark;
  templateLayoutFactory: { createClass: (template: string) => unknown };
}
declare global {
  interface Window {
    ymaps?: YMaps;
  }
}

let loading: Promise<YMaps> | null = null;
function loadYmaps(key: string): Promise<YMaps> {
  if (window.ymaps) return Promise.resolve(window.ymaps);
  loading ??= new Promise((resolve, reject) => {
    const s = document.createElement("script");
    s.src = `https://api-maps.yandex.ru/2.1/?apikey=${encodeURIComponent(key)}&lang=uz_UZ`;
    s.async = true;
    s.onload = () => (window.ymaps ? window.ymaps.ready(() => resolve(window.ymaps!)) : reject(new Error("ymaps")));
    s.onerror = () => reject(new Error("ymaps"));
    document.head.appendChild(s);
  });
  return loading;
}

/**
 * Filiallar xaritasi. Skript bo'lim ekranga yaqinlashgandagina yuklanadi
 * (sahifaning birinchi ochilishi og'irlashmaydi). Chapdagi ro'yxatdagi
 * `[data-map-focus="<id>"]` havolasi bosilsa — xarita o'sha filialga uchadi;
 * xarita yuklanmagan bo'lsa, havola odatdagidek tashqi xaritani ochadi.
 * Sichqoncha g'ildiragi bilan kattalashtirish o'chirilgan — sahifa aylanishini
 * "tutib" qolmasin.
 */
export function BranchMap({ branches, apiKey }: { branches: MapBranch[]; apiKey: string }) {
  const box = useRef<HTMLDivElement>(null);
  const [state, setState] = useState<"idle" | "ready" | "error">("idle");
  // Tanlangan filial — xaritaning pastki burchagidagi brend kartochkasida.
  const [selected, setSelected] = useState<MapBranch | null>(null);

  useEffect(() => {
    const el = box.current;
    if (!el) return;
    let map: YMap | null = null;
    const marks = new Map<string, YPlacemark>();
    let cancelled = false;

    /* Tanlangan filial: ro'yxatda ham, xaritadagi belgida ham ajralib turadi. */
    const select = (id: string) => {
      document.querySelectorAll("[data-branch-item]").forEach((li) => {
        li.toggleAttribute("data-active", li.getAttribute("data-branch-item") === id);
      });
      marks.forEach((pm, key) => {
        pm.properties.set("active", key === id);
        pm.options.set("zIndex", key === id ? 1000 : 0);
      });
      setSelected(branches.find((x) => x.id === id) ?? null);
    };

    const onFocusClick = (e: MouseEvent) => {
      const a = (e.target as HTMLElement).closest<HTMLElement>("[data-map-focus]");
      if (!a || !map) return;
      const b = branches.find((x) => x.id === a.dataset.mapFocus);
      const pm = b && marks.get(b.id);
      if (!b || !pm) return;
      e.preventDefault();
      select(b.id);
      void map.setCenter([b.lat, b.lng], 15, { duration: 400 });
      if (window.matchMedia("(max-width: 1023px)").matches) {
        el.scrollIntoView({ behavior: "smooth", block: "center" });
      }
    };

    const init = () =>
      loadYmaps(apiKey)
        .then((ymaps) => {
          if (cancelled) return;
          map = new ymaps.Map(
            el,
            { center: [40.75, 72.2], zoom: 9, controls: [] },
            // Do'kon/kafe kabi joylar bosilmaydi — e'tibor faqat filiallarda.
            { suppressMapOpenBlock: true, yandexMapDisablePoiInteractivity: true },
          );
          map.behaviors.disable("scrollZoom");
          map.controls.add("zoomControl", { size: "small", position: { right: 12, top: 12 } });
          // Brend belgisi: binafsha tugmacha, ichida filial raqami; tanlangani — sariq.
          // Uslubi globals.css > .hs-pin. Kursor kelganda filial nomi chiqadi.
          const pin = ymaps.templateLayoutFactory.createClass(
            '<div class="hs-pin{% if properties.active %} is-active{% endif %}">' +
              '<span class="hs-pin-dot">$[properties.index]</span>' +
              '<span class="hs-pin-label">{{ properties.hintContent }}</span>' +
            "</div>",
          );
          for (const b of branches) {
            const pm = new ymaps.Placemark(
              [b.lat, b.lng],
              {
                hintContent: b.title,
                index: b.index,
                active: false,
              },
              {
                iconLayout: pin,
                iconShape: { type: "Circle", coordinates: [0, -26], radius: 22 },
                hasBalloon: false,
                hasHint: false,
              },
            );
            pm.events.add("click", () => {
              select(b.id);
              void map?.setCenter([b.lat, b.lng], 15, { duration: 400 });
            });
            map.geoObjects.add(pm);
            marks.set(b.id, pm);
          }
          const bounds = map.geoObjects.getBounds();
          if (bounds) map.setBounds(bounds, { checkZoomRange: true, zoomMargin: 48 });
          setState("ready");
        })
        .catch(() => !cancelled && setState("error"));

    const io = new IntersectionObserver(
      (entries) => {
        if (entries.some((x) => x.isIntersecting)) {
          io.disconnect();
          void init();
        }
      },
      { rootMargin: "400px" },
    );
    io.observe(el);
    document.addEventListener("click", onFocusClick);
    return () => {
      cancelled = true;
      io.disconnect();
      document.removeEventListener("click", onFocusClick);
      map?.destroy();
    };
  }, [branches, apiKey]);

  return (
    <div className="relative h-full min-h-[22rem] overflow-hidden rounded-[24px] border border-line bg-ground-2 lg:min-h-[32rem]">
      <div ref={box} className="absolute inset-0" aria-label="Filiallar xaritasi" role="region" />
      {selected ? (
        <div className="absolute inset-x-3 bottom-3 z-10 rounded-[18px] border border-line bg-ground/95 p-4 shadow-[0_20px_40px_-20px_rgba(47,24,72,0.5)] backdrop-blur sm:inset-x-auto sm:left-4 sm:bottom-4 sm:w-80 sm:p-5">
          <button
            type="button"
            onClick={() => setSelected(null)}
            aria-label="Yopish"
            className="absolute right-3 top-3 flex h-8 w-8 items-center justify-center rounded-[10px] text-ink-3 hover:bg-ground-2 hover:text-ink"
          >
            ✕
          </button>
          <p className="numeral text-sm tracking-[0.2em] text-purple">FILIAL {selected.index}</p>
          <p className="display mt-1 pr-8 text-lg leading-snug">{selected.title}</p>
          <p className="mt-2 text-sm leading-relaxed text-ink-2">{selected.address}</p>
          <p className="mt-1 text-sm text-ink-3">Ish vaqti: {selected.hours}</p>
          <div className="mt-4 grid grid-cols-2 gap-2">
            <a
              href={`https://yandex.uz/maps/?rtext=~${selected.lat},${selected.lng}&rtt=auto`}
              target="_blank"
              rel="noopener noreferrer"
              className="btn btn-primary !min-h-10 !px-3 text-sm"
            >
              Yo&apos;l ko&apos;rsatish
            </a>
            <a href={`tel:${selected.phone}`} className="btn btn-outline !min-h-10 !px-3 text-sm">
              Qo&apos;ng&apos;iroq
            </a>
          </div>
          <a href={selected.href} className="mt-3 inline-block text-sm font-semibold text-purple hover:underline">
            Filial sahifasi &rarr;
          </a>
        </div>
      ) : null}
      {state !== "ready" ? (
        <div className="pointer-events-none absolute inset-0 flex items-center justify-center p-6 text-center text-sm text-ink-3">
          {state === "error"
            ? "Xarita yuklanmadi — chapdagi \"Xaritada\" havolasi tashqi xaritani ochadi."
            : "Xarita yuklanmoqda…"}
        </div>
      ) : null}
    </div>
  );
}
