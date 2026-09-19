"use client";

import Link from "next/link";
import { branches } from "@/data/branches";
import { site } from "@/data/site";
import { useLeadSubmit } from "@/lib/lead";

/**
 * "Ariza qoldirish" — the low-friction path for visitors who will not phone.
 *
 * It is a real <form> posting to /api/lead, so it still works if JavaScript is
 * disabled or has not finished downloading: the route answers with a redirect
 * to /rahmat. When JS is available this intercepts the submit and reports the
 * result inline instead, without losing the page.
 */
/* Ruscha sahifalar (/ru/...) uchun. O'zbekcha matnlar avvalgidek — lotin
   nusxadan kirillchasini `tools/uz-kr-build.mjs` o'zi yasaydi. */
const TEXT = {
  uz: {
    kicker: "Ariza qoldiring",
    title: "Sizga o'zimiz qo'ng'iroq qilamiz",
    lead: "Qo'ng'iroq qilishga vaqtingiz yo'qmi? Ism va telefon raqamingizni qoldiring — mutaxassisimiz o'zi bog'lanadi va savollaringizga javob beradi.",
    orCall: "Yoki hoziroq qo'ng'iroq qiling:",
    name: "Ismingiz",
    namePh: "Anvar",
    phone: "Telefon",
    phoneTitle: "Telefon raqamini kiriting, masalan: +998 90 123 45 67",
    phoneHelp: "Shu raqamga qo'ng'iroq qilamiz.",
    branch: "Qaysi filial sizga qulay?",
    any: "Farqi yo'q",
    other: "Boshqa viloyatdaman — yetkazib berasizmi?",
    note: "Nima qiziqtiradi?",
    notePh: "Masalan: muzlatgich, muddatli to'lov shartlari",
    specialTitle: "Mahsulot sizda yo'q — boshqa joyda ko'rganman",
    specialText: "Uni ham muddatli to'lovga rasmiylashtirib beramiz. Nomi, narxi va qayerdaligini yuqorida yozib qoldiring.",
    sending: "Yuborilmoqda…",
    submit: "Arizani yuborish",
    privacy1: "Ma'lumotlaringiz faqat siz bilan bog'lanish uchun ishlatiladi. Arizani yuborish orqali",
    privacyLink: "shaxsiy ma'lumotlarni qayta ishlashga",
    privacy2: "rozilik bildirasiz.",
    ok: "Arizangiz qabul qilindi. Tez orada bog'lanamiz — rahmat!",
  },
  ru: {
    kicker: "Оставьте заявку",
    title: "Мы сами вам перезвоним",
    lead: "Нет времени звонить? Оставьте имя и номер телефона — специалист свяжется с вами и ответит на вопросы.",
    orCall: "Или позвоните прямо сейчас:",
    name: "Ваше имя",
    namePh: "Анвар",
    phone: "Телефон",
    phoneTitle: "Введите номер телефона, например: +998 90 123 45 67",
    phoneHelp: "Перезвоним на этот номер.",
    branch: "Какой магазин вам удобнее?",
    any: "Неважно",
    other: "Я из другого региона — доставите?",
    note: "Что вас интересует?",
    notePh: "Например: холодильник, условия рассрочки",
    specialTitle: "Такого товара у вас нет — я видел его в другом месте",
    specialText: "Оформим в рассрочку и его. Напишите выше название, цену и где вы его видели.",
    sending: "Отправляем…",
    submit: "Отправить заявку",
    privacy1: "Данные используются только для связи с вами. Отправляя заявку, вы даёте",
    privacyLink: "согласие на обработку персональных данных",
    privacy2: ".",
    ok: "Заявка принята. Скоро свяжемся с вами — спасибо!",
  },
};

const CITY_RU: Record<string, string> = { Shahrixon: "Шахрихан", Asaka: "Асака", Andijon: "Андижан" };

export function LeadForm({
  branchId,
  lang = "uz",
  page,
  lead,
}: {
  branchId?: string;
  lang?: "uz" | "ru";
  /** Ariza qaysi sahifadan kelgani (Telegram xabarida ko'rinadi). */
  page?: string;
  /** Sarlavha ostidagi matn o'rniga — yo'nalish sahifasiga mos. */
  lead?: string;
}) {
  /* Yuborish mantiqi `lib/lead.ts` da — bosh qismdagi qisqa forma ham
     aynan shuni ishlatadi, shunda ikkalasi hech qachon ajralib ketmaydi. */
  const { status, onSubmit } = useLeadSubmit();
  const t = TEXT[lang];

  return (
    <section id="ariza" aria-labelledby="lead-title" className="bg-ground py-16 sm:py-24">
      <div className="mx-auto max-w-7xl px-5 sm:px-8">
        <div className="grid gap-12 lg:grid-cols-[0.9fr_1.1fr] lg:gap-16">
          <div>
            <p className="kicker">{t.kicker}</p>
            <h2 id="lead-title" className="display mt-3 text-4xl sm:text-5xl">
              {t.title}
            </h2>
            <p className="mt-5 max-w-md text-lg leading-relaxed text-ink-2">{lead ?? t.lead}</p>
            <p className="mt-6 text-sm text-ink-3">
              {t.orCall}{" "}
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
            <input type="hidden" name="page" value={page ?? (branchId ? `/filiallar/${branchId}` : "/")} />
            {/* Mijoz qayerdan kelgani — app/layout.tsx dagi SOURCE_SCRIPT yuborishdan oldin to'ldiradi. */}
            <input type="hidden" name="src" defaultValue="" />

            <div className="grid gap-5 sm:grid-cols-2">
              <div className="sm:col-span-1">
                <label htmlFor="lead-name" className="block text-sm font-semibold text-ink">
                  {t.name} <span className="text-purple">*</span>
                </label>
                <input
                  id="lead-name"
                  name="name"
                  type="text"
                  required
                  minLength={2}
                  maxLength={80}
                  autoComplete="name"
                  /* ym-disable-keys: Webvisor yozilgan matnni Yandex'ga yubormaydi. */
                  className="ym-disable-keys mt-2 h-12 w-full rounded-xl border border-line bg-ground px-4 text-ink outline-none transition-colors focus:border-purple"
                  placeholder={t.namePh}
                />
              </div>

              <div className="sm:col-span-1">
                <label htmlFor="lead-phone" className="block text-sm font-semibold text-ink">
                  {t.phone} <span className="text-purple">*</span>
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
                  title={t.phoneTitle}
                  aria-describedby="lead-phone-help"
                  className="ym-disable-keys mt-2 h-12 w-full rounded-xl border border-line bg-ground px-4 text-ink outline-none transition-colors focus:border-purple"
                  placeholder="+998 90 123 45 67"
                />
                <p id="lead-phone-help" className="mt-2 text-xs text-ink-3">
                  {t.phoneHelp}
                </p>
              </div>

              <div className="sm:col-span-2">
                <label htmlFor="lead-branch" className="block text-sm font-semibold text-ink">
                  {t.branch}
                </label>
                <select
                  id="lead-branch"
                  name="branch"
                  defaultValue={branchId ?? ""}
                  className="mt-2 h-12 w-full rounded-xl border border-line bg-ground px-4 text-ink outline-none transition-colors focus:border-purple"
                >
                  <option value="">{t.any}</option>
                  {branches.map((b) => (
                    <option key={b.id} value={b.id}>
                      {lang === "ru" ? CITY_RU[b.city] ?? b.city : b.city} — {b.landmark}
                    </option>
                  ))}
                  {/* Filiallar Andijon viloyatida, lekin yetkazib berish butun
                      mamlakat bo'ylab. Boshqa viloyatdagi odam ro'yxatdan o'ziga
                      begona filialni tanlashga majbur bo'lmasin — bu ariza ham
                      boshqacha ishlanadi, operator yetkazish shartlarini
                      aytishi kerak. */}
                  <option value="boshqa-viloyat">{t.other}</option>
                </select>
              </div>

              <div className="sm:col-span-2">
                <label htmlFor="lead-note" className="block text-sm font-semibold text-ink">
                  {t.note}
                </label>
                <textarea
                  id="lead-note"
                  name="note"
                  rows={3}
                  maxLength={500}
                  className="ym-disable-keys mt-2 w-full rounded-xl border border-line bg-ground p-4 text-ink outline-none transition-colors focus:border-purple"
                  placeholder={t.notePh}
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
                    <span className="font-semibold text-ink">{t.specialTitle}</span>
                    <br />
                    {t.specialText}
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
                {status.kind === "sending" ? t.sending : t.submit}
              </button>
              {/* Forma ism va telefon raqamini yig'adi — bu shaxsga doir
                  ma'lumot. O'zbekiston qonuni bo'yicha odam nima uchun va
                  kimga berayotganini bilishi kerak. */}
              <p className="text-xs leading-relaxed text-ink-3">
                {t.privacy1}{" "}
                <Link href="/maxfiylik" className="font-semibold text-purple underline-offset-2 hover:underline">
                  {t.privacyLink}
                </Link>
                {lang === "ru" ? "" : " "}
                {t.privacy2}
              </p>
            </div>

            {/* Result is announced, not just coloured. */}
            <div aria-live="polite" className="mt-5 empty:mt-0">
              {status.kind === "ok" ? (
                <p className="rounded-xl bg-purple-50 px-5 py-4 font-semibold text-purple">
                  {t.ok}
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
