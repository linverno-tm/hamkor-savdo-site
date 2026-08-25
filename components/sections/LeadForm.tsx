"use client";

import { useState } from "react";
import Link from "next/link";
import { branches } from "@/data/branches";
import { site } from "@/data/site";

type Status = { kind: "idle" | "sending" | "ok" } | { kind: "error"; message: string };

/**
 * "Ariza qoldirish" — the low-friction path for visitors who will not phone.
 *
 * It is a real <form> posting to /api/lead, so it still works if JavaScript is
 * disabled or has not finished downloading: the route answers with a redirect
 * to /rahmat. When JS is available this intercepts the submit and reports the
 * result inline instead, without losing the page.
 */
export function LeadForm({ branchId }: { branchId?: string }) {
  const [status, setStatus] = useState<Status>({ kind: "idle" });

  async function onSubmit(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault();
    const form = e.currentTarget;
    setStatus({ kind: "sending" });
    try {
      const res = await fetch(form.action, {
        method: "POST",
        body: new FormData(form),
        headers: { accept: "application/json" },
      });
      const data = await res.json().catch(() => ({}));
      if (res.ok && data.ok) {
        setStatus({ kind: "ok" });
        form.reset();
      } else {
        setStatus({
          kind: "error",
          message: data.message ?? "Ariza yuborilmadi. Telefon orqali bog'laning.",
        });
      }
    } catch {
      setStatus({
        kind: "error",
        message: "Internet aloqasida muammo. Telefon orqali bog'laning.",
      });
    }
  }

  return (
    <section id="ariza" aria-labelledby="lead-title" className="bg-ground py-16 sm:py-24">
      <div className="mx-auto max-w-7xl px-5 sm:px-8">
        <div className="grid gap-12 lg:grid-cols-[0.9fr_1.1fr] lg:gap-16">
          <div>
            <p className="kicker">Ariza qoldiring</p>
            <h2 id="lead-title" className="display mt-3 text-4xl sm:text-5xl">
              Sizga{" "}
              <span className="relative z-0">
                <span className="mark">o&apos;zimiz</span>
              </span>{" "}
              qo&apos;ng&apos;iroq qilamiz
            </h2>
            <p className="mt-5 max-w-md text-lg leading-relaxed text-ink-2">
              Qo&apos;ng&apos;iroq qilishga vaqtingiz yo&apos;qmi? Ism va telefon raqamingizni
              qoldiring — mutaxassisimiz o&apos;zi bog&apos;lanadi va savollaringizga javob
              beradi.
            </p>
            <p className="mt-6 text-sm text-ink-3">
              Yoki hoziroq qo&apos;ng&apos;iroq qiling:{" "}
              <a
                href={`tel:${site.phone}`}
                className="font-semibold text-purple underline-offset-4 hover:underline"
              >
                {site.phoneDisplay}
              </a>
            </p>
          </div>

          <form
            action="/api/lead.php"
            method="post"
            onSubmit={onSubmit}
            className="card p-7 sm:p-9"
          >
            {/* Honeypot — hidden from people, irresistible to bots. */}
            <div aria-hidden="true" className="pointer-events-none absolute left-[-9999px] top-0 h-0 w-0 overflow-hidden">
              <label htmlFor="website">Saytingiz</label>
              <input id="website" name="website" type="text" tabIndex={-1} autoComplete="off" />
            </div>
            <input type="hidden" name="page" value={branchId ? `/filiallar/${branchId}` : "/"} />

            <div className="grid gap-5 sm:grid-cols-2">
              <div className="sm:col-span-1">
                <label htmlFor="lead-name" className="block text-sm font-semibold text-ink">
                  Ismingiz <span className="text-purple">*</span>
                </label>
                <input
                  id="lead-name"
                  name="name"
                  type="text"
                  required
                  minLength={2}
                  maxLength={80}
                  autoComplete="name"
                  className="mt-2 h-12 w-full rounded-xl border border-line bg-ground px-4 text-ink outline-none transition-colors focus:border-purple"
                  placeholder="Anvar"
                />
              </div>

              <div className="sm:col-span-1">
                <label htmlFor="lead-phone" className="block text-sm font-semibold text-ink">
                  Telefon <span className="text-purple">*</span>
                </label>
                <input
                  id="lead-phone"
                  name="phone"
                  type="tel"
                  required
                  inputMode="tel"
                  maxLength={24}
                  autoComplete="tel"
                  /* `type="tel"` hech narsani tekshirmaydi — u faqat telefonda
                     raqamli klaviatura ochadi. Bu shablon esa "asdasd" kabi
                     yozuvni brauzerning o'zi to'xtatadi. Serverdagi tekshiruv
                     baribir qoladi: brauzerni chetlab o'tish oson. Bo'sh joy,
                     qavs va chiziqchaga ruxsat, chunki odam raqamni
                     "+998 90 123 45 67" ko'rinishida yozadi. */
                  pattern="[+]?[0-9()\-\s]{9,24}"
                  title="Telefon raqamini kiriting, masalan: +998 90 123 45 67"
                  aria-describedby="lead-phone-help"
                  className="mt-2 h-12 w-full rounded-xl border border-line bg-ground px-4 text-ink outline-none transition-colors focus:border-purple"
                  placeholder="+998 90 123 45 67"
                />
                <p id="lead-phone-help" className="mt-2 text-xs text-ink-3">
                  Shu raqamga qo&apos;ng&apos;iroq qilamiz.
                </p>
              </div>

              <div className="sm:col-span-2">
                <label htmlFor="lead-branch" className="block text-sm font-semibold text-ink">
                  Qaysi filial sizga qulay?
                </label>
                <select
                  id="lead-branch"
                  name="branch"
                  defaultValue={branchId ?? ""}
                  className="mt-2 h-12 w-full rounded-xl border border-line bg-ground px-4 text-ink outline-none transition-colors focus:border-purple"
                >
                  <option value="">Farqi yo&apos;q</option>
                  {branches.map((b) => (
                    <option key={b.id} value={b.id}>
                      {b.city} — {b.landmark}
                    </option>
                  ))}
                </select>
              </div>

              <div className="sm:col-span-2">
                <label htmlFor="lead-note" className="block text-sm font-semibold text-ink">
                  Nima qiziqtiradi?
                </label>
                <textarea
                  id="lead-note"
                  name="note"
                  rows={3}
                  maxLength={500}
                  className="mt-2 w-full rounded-xl border border-line bg-ground p-4 text-ink outline-none transition-colors focus:border-purple"
                  placeholder="Masalan: muzlatgich, muddatli to'lov shartlari"
                />
              </div>

              {/* Do'konda yo'q mahsulot uchun kelgan ariza boshqacha ishlanadi:
                  operator avval narxini aniqlashi kerak. Bitta belgi qo'yilsa,
                  Telegramdagi xabarga alohida xeshteg tushadi va call-center
                  uni darhol ajratib oladi. JavaScriptsiz ham ishlaydi —
                  oddiy checkbox. */}
              <div className="sm:col-span-2">
                <label
                  htmlFor="lead-special"
                  className="flex cursor-pointer items-start gap-3 rounded-xl border border-line bg-ground p-4"
                >
                  <input
                    id="lead-special"
                    name="special"
                    type="checkbox"
                    value="ha"
                    className="mt-0.5 h-5 w-5 shrink-0 accent-[var(--purple)]"
                  />
                  <span className="text-sm leading-relaxed text-ink-2">
                    <span className="font-semibold text-ink">
                      Mahsulot sizda yo&apos;q — boshqa joyda ko&apos;rganman
                    </span>
                    <br />
                    Uni ham muddatli to&apos;lovga rasmiylashtirib beramiz. Nomi, narxi va
                    qayerdaligini yuqorida yozib qoldiring.
                  </span>
                </label>
              </div>
            </div>

            <div className="mt-6 flex flex-wrap items-center gap-4">
              <button
                type="submit"
                disabled={status.kind === "sending"}
                className="btn btn-primary disabled:opacity-60"
              >
                {status.kind === "sending" ? "Yuborilmoqda…" : "Arizani yuborish"}
              </button>
              {/* Forma ism va telefon raqamini yig'adi — bu shaxsga doir
                  ma'lumot. O'zbekiston qonuni bo'yicha odam nima uchun va
                  kimga berayotganini bilishi kerak. */}
              <p className="text-xs leading-relaxed text-ink-3">
                Ma&apos;lumotlaringiz faqat siz bilan bog&apos;lanish uchun ishlatiladi.
                Arizani yuborish orqali{" "}
                <Link href="/maxfiylik" className="font-semibold text-purple underline-offset-2 hover:underline">
                  shaxsiy ma&apos;lumotlarni qayta ishlashga
                </Link>{" "}
                rozilik bildirasiz.
              </p>
            </div>

            {/* Result is announced, not just coloured. */}
            <div aria-live="polite" className="mt-5 empty:mt-0">
              {status.kind === "ok" ? (
                <p className="rounded-xl bg-purple-50 px-5 py-4 font-semibold text-purple">
                  Arizangiz qabul qilindi. Tez orada bog&apos;lanamiz — rahmat!
                </p>
              ) : null}
              {status.kind === "error" ? (
                <p className="rounded-xl border border-purple-100 bg-ground-2 px-5 py-4 text-ink-2">
                  {status.message}{" "}
                  <a
                    href={`tel:${site.phone}`}
                    className="font-semibold text-purple underline-offset-4 hover:underline"
                  >
                    {site.phoneDisplay}
                  </a>
                </p>
              ) : null}
            </div>
          </form>
        </div>
      </div>
    </section>
  );
}
