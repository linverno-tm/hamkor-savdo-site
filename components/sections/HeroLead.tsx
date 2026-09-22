"use client";

import Link from "next/link";
import { branches } from "@/data/branches";
import { site } from "@/data/site";
import { useLeadSubmit } from "@/lib/lead";

/**
 * Bosh qismning o'ng ustuni — qisqa ariza formasi.
 *
 * Ilgari bu yerda surat va raqamlar kartochkasi turardi. Kartochkadagi
 * raqamlar sahifaning pastidagi "Ko'lam" bo'limida allaqachon bor edi, ya'ni
 * u yangi hech narsa aytmasdi. Muddatli to'lovda mijoz saytdan xarid
 * qilmaydi — u qo'ng'iroq kutadi. Shuning uchun eng ko'p e'tibor tushadigan
 * joyni saytning asl maqsadi egallaydi.
 *
 * Faqat ikkita majburiy maydon: ism va telefon. Filial ixtiyoriy — bilmasa
 * ham yubora oladi, operator o'zi aniqlaydi. Pastdagi to'liq forma joyida
 * qoladi: u yerda izoh va "bizda yo'q mahsulot" bandi bor.
 *
 * JavaScript'siz ham ishlaydi — oddiy <form>, server `/rahmat/` ga
 * yo'naltiradi.
 */
export function HeroLead() {
  const { status, onSubmit } = useLeadSubmit();

  return (
    <form
      action="/api/lead.php"
      method="post"
      onSubmit={onSubmit}
      aria-labelledby="hero-lead-title"
      className="on-purple relative rounded-[24px] bg-purple p-6 text-white shadow-[0_30px_60px_-30px_rgba(47,24,72,0.6)] sm:p-8"
    >
      <div aria-hidden="true" className="pointer-events-none absolute left-[-9999px] top-0 h-0 w-0 overflow-hidden">
        <label htmlFor="hero-website">Saytingiz</label>
        <input id="hero-website" name="website" type="text" tabIndex={-1} autoComplete="off" />
      </div>
      <input type="hidden" name="page" value="/" />
      <input type="hidden" name="src" defaultValue="" />

      <p id="hero-lead-title" className="display text-2xl leading-tight sm:text-[1.75rem]">
        Ariza qoldiring
      </p>
      <p className="mt-2 text-sm leading-relaxed text-on-purple-2">
        Ism va telefon raqamingizni qoldiring — sizga qo&apos;ng&apos;iroq qilamiz va mahsulot
        tanlashda yordam beramiz.
      </p>

      <div className="mt-5 space-y-3.5">
        <div>
          <label htmlFor="hero-name" className="block text-sm font-medium">
            Ismingiz
          </label>
          <input
            id="hero-name"
            name="name"
            type="text"
            required
            minLength={2}
            maxLength={80}
            autoComplete="name"
            className="ym-disable-keys mt-1.5 h-12 w-full rounded-[12px] border border-white/25 bg-white/10 px-4 text-white outline-none transition-colors placeholder:text-on-purple-2 focus:border-yellow"
            placeholder="Anvar"
          />
        </div>

        <div>
          <label htmlFor="hero-phone" className="block text-sm font-medium">
            Telefon
          </label>
          <input
            id="hero-phone"
            name="phone"
            type="tel"
            required
            inputMode="tel"
            maxLength={24}
            autoComplete="tel"
            pattern="[+]?[0-9()\-\s]{9,24}"
            title="Telefon raqamini kiriting, masalan: +998 90 123 45 67"
            className="ym-disable-keys mt-1.5 h-12 w-full rounded-[12px] border border-white/25 bg-white/10 px-4 text-white outline-none transition-colors placeholder:text-on-purple-2 focus:border-yellow"
            placeholder="+998 90 123 45 67"
          />
        </div>

        <div>
          <label htmlFor="hero-branch" className="block text-sm font-medium">
            Qaysi filial <span className="font-normal text-on-purple-2">— ixtiyoriy</span>
          </label>
          <select
            id="hero-branch"
            name="branch"
            defaultValue=""
            className="mt-1.5 h-12 w-full rounded-[12px] border border-white/25 bg-white/10 px-4 text-white outline-none transition-colors focus:border-yellow"
          >
            {/* Ro'yxatdagi matn oq fonli ochiluvchi oynada ko'rinadi, shuning
                uchun rangi ataylab to'q — merosga o'tgan oq rang u yerda
                o'qilmas edi. */}
            <option value="" className="text-ink">
              Farqi yo&apos;q
            </option>
            {branches.map((b) => (
              <option key={b.id} value={b.id} className="text-ink">
                {b.city} — {b.landmark}
              </option>
            ))}
          </select>
        </div>
      </div>

      <button
        type="submit"
        disabled={status.kind === "sending"}
        className="btn btn-yellow btn-lift mt-5 w-full disabled:opacity-60"
      >
        {status.kind === "sending" ? "Yuborilmoqda…" : "Arizani yuborish"}
      </button>

      <p className="mt-3 text-xs leading-relaxed text-on-purple-2">
        Yuborish orqali{" "}
        <Link href="/maxfiylik" className="font-semibold text-white underline-offset-2 hover:underline">
          shaxsiy ma&apos;lumotlarni qayta ishlashga
        </Link>{" "}
        rozilik bildirasiz.
      </p>

      <div aria-live="polite" className="mt-4 empty:mt-0">
        {status.kind === "ok" ? (
          <p className="rounded-xl bg-white px-5 py-4 font-semibold text-purple">
            Arizangiz qabul qilindi. Tez orada bog&apos;lanamiz — rahmat!
          </p>
        ) : null}
        {status.kind === "error" ? (
          <p className="rounded-xl border border-white/30 px-5 py-4 text-sm text-on-purple-2">
            {status.message}{" "}
            <a href={`tel:${site.phone}`} className="font-semibold text-white underline-offset-4 hover:underline">
              {site.phoneDisplay}
            </a>
          </p>
        ) : null}
      </div>
    </form>
  );
}
