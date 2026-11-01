"use client";

import Link from "next/link";
import { branches } from "@/data/branches";
import { site } from "@/data/site";
import { telefonInput } from "@/lib/telefon";
import { useLeadSubmit } from "@/lib/lead";
import { SuccessMark } from "@/components/ui/SuccessMark";

/**
 * Bosh qismdagi qisqa ariza (01.11 redizayn).
 *
 * Maydonlar, nomlar va manzil eski HeroLead bilan bir xil — ariza o'sha
 * `/api/lead.php` ga (Telegram + admin panel) boradi, yuborish mantiqi ham
 * umumiy (`lib/lead.ts`). Faqat ko'rinish boshqa: kompyuterda bitta qator
 * "chek" ko'rinishida.
 *
 * Telefonda A/B sinov ("tel-forma", lib/ab.ts) saqlanadi: forma faqat "b"
 * guruhda ko'rinadi (app/uy.css > .uh-form).
 */
export function UyTezForma() {
  const { status, onSubmit } = useLeadSubmit();

  return (
    <form action="/api/lead.php" method="post" onSubmit={onSubmit} aria-labelledby="hero-lead-title" className="uh-form">
      <div aria-hidden="true" className="pointer-events-none absolute left-[-9999px] top-0 h-0 w-0 overflow-hidden">
        <label htmlFor="hero-website">Saytingiz</label>
        <input id="hero-website" name="website" type="text" tabIndex={-1} autoComplete="off" />
      </div>
      <input type="hidden" name="page" value="/" />
      <input type="hidden" name="src" defaultValue="" />

      <p id="hero-lead-title" className="uh-form-title">
        Raqamingizni qoldiring — o&apos;zimiz qo&apos;ng&apos;iroq qilamiz
      </p>

      <div className="uh-form-row">
        <div className="uh-f">
          <label htmlFor="hero-name">Ismingiz</label>
          <input
            id="hero-name"
            name="name"
            type="text"
            required
            minLength={2}
            maxLength={80}
            autoComplete="name"
            className="hero-field ym-disable-keys"
            placeholder="Anvar"
          />
        </div>
        <div className="uh-f">
          <label htmlFor="hero-phone">Telefon</label>
          <input
            id="hero-phone"
            onInput={telefonInput}
            name="phone"
            type="tel"
            required
            inputMode="tel"
            maxLength={24}
            autoComplete="tel"
            pattern="[+]?[0-9\(\)\-\s]{9,24}"
            title="Telefon raqamini kiriting, masalan: +998 90 123 45 67"
            className="hero-field ym-disable-keys"
            placeholder="+998 90 123 45 67"
          />
        </div>
        <div className="uh-f">
          <label htmlFor="hero-branch">
            Filial <span className="uh-f-opt">— ixtiyoriy</span>
          </label>
          <select id="hero-branch" name="branch" defaultValue="" className="hero-field pick">
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
        <button type="submit" disabled={status.kind === "sending"} className="btn btn-yellow uh-form-btn">
          {status.kind === "sending" ? "Yuborilmoqda…" : "Arizani yuborish"}
        </button>
      </div>

      <p className="uh-form-note">
        Yuborish orqali{" "}
        <Link href="/maxfiylik">shaxsiy ma&apos;lumotlarni qayta ishlashga</Link> rozilik bildirasiz.
      </p>

      <div aria-live="polite" className="uh-form-status">
        {status.kind === "ok" ? (
          <p className="uh-form-ok">
            <SuccessMark size="sm" />
            Arizangiz qabul qilindi. Tez orada bog&apos;lanamiz — rahmat!
          </p>
        ) : null}
        {status.kind === "error" ? (
          <p className="uh-form-err">
            {status.message} <a href={`tel:${site.phone}`}>{site.phoneDisplay}</a>
          </p>
        ) : null}
      </div>
    </form>
  );
}
