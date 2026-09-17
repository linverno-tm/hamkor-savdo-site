import type { Metadata } from "next";
import { Outfit, Bebas_Neue } from "next/font/google";
import { site } from "@/data/site";
import { SITE_URL, absolute } from "@/lib/seo";
import { Reveal } from "@/components/ui/Reveal";
import { MetrikaPageview } from "@/components/ui/MetrikaPageview";
import { YM_ID } from "@/lib/metrika";
import "./globals.css";

/**
 * Corporate fonts are TT Commons and Bebas Neue Pro (both commercial).
 * Outfit stands in for TT Commons — same geometric sans role — and Bebas Neue
 * is the free release of the display face. One variable axis plus one static
 * weight keeps the font payload around 30 KB on a cold load.
 */
const outfit = Outfit({
  variable: "--font-outfit",
  subsets: ["latin"],
  display: "swap",
});

const bebas = Bebas_Neue({
  variable: "--font-bebas",
  subsets: ["latin"],
  weight: "400",
  display: "swap",
});

const description =
  `HAMKOR SAVDO — Andijon viloyatidagi ${site.facts.branchCount} ta filial: tilla, texnika va mebel. ${site.facts.productCount.replace("+", " dan ortiq")} mahsulot, ${site.facts.installmentMonthsMax} oygacha muddatli to'lov, bepul yetkazib berish va o'rnatish.`;

export const metadata: Metadata = {
  metadataBase: new URL(SITE_URL),
  title: "HAMKOR SAVDO — Oilangizga ishonchli hamkor",
  description,
  // `keywords` meta tegini qidiruv tizimlari 2009-yildan beri e'tiborga
  // olmaydi (Google buni ochiq e'lon qilgan) — shuning uchun olib tashlandi.
  alternates: {
    canonical: absolute("/"),
    languages: {
      uz: absolute("/"),
      "uz-Cyrl": absolute("/uz-kr/"),
    },
  },
  openGraph: {
    title: "HAMKOR SAVDO — Oilangizga ishonchli hamkor",
    description,
    locale: "uz_UZ",
    type: "website",
    siteName: site.name,
  },
};

export const viewport = {
  themeColor: "#5a3089",
};

/**
 * Til aniqlash — Kirill (uz-kr) sahifa React'siz, sof HTML bo'lgani uchun
 * (tools/uz-kr-build.mjs, hydration mos kelmasligining oldini olish uchun
 * React skriptlarini olib tashlaydi) bu alohida, juda kichik, Next'ga
 * bog'liq bo'lmagan skript — shuning uchun `data-keep` bilan belgilangan va
 * shu skript ikkala variantda ham saqlanib qoladi.
 *
 * Qoida: ruscha qurilma -> Kirill, boshqa hamma narsa (jumladan ingliz) ->
 * standart Lotin. Odam qaysi variantni ko'rib tursa, shuni "tanlov" sifatida
 * eslab qoladi — keyingi safar ochilganda avtomatik almashtirmaydi, hatto
 * ruscha brauzerda ham (ataylab Lotinni tanlagan bo'lishi mumkin).
 */
const LANG_DETECT_SCRIPT = `(function(){try{
var onCyr = location.pathname.indexOf('/uz-kr') === 0;
var saved = localStorage.getItem('hs_lang');
if (!saved && !onCyr && /^ru\\b/i.test(navigator.language || '')) {
  localStorage.setItem('hs_lang', 'uz-kr');
  window.__hsRedirecting = true;
  sessionStorage.setItem('hs_ref', document.referrer);
  location.replace('/uz-kr' + location.pathname + location.search + location.hash);
  return;
}
localStorage.setItem('hs_lang', onCyr ? 'uz-kr' : 'uz');
}catch(e){}})();`;

/**
 * Yandex Metrika. Oddiy <script> (Next <Script> emas) va `data-keep` —
 * kirill nusxada React skriptlari olib tashlanadi, bu esa qolishi kerak.
 *
 * - Faqat haqiqiy domenda ishlaydi: localhost'dagi sinovlar statistikani
 *   buzmasin. `?_ym_debug=1` bilan istalgan joyda yoqib tekshirish mumkin.
 * - Kirillga avtomatik yo'naltirilganda asl manba (Instagram, Google)
 *   sessionStorage orqali uzatiladi, aks holda hammasi "o'z saytidan" bo'lib
 *   ko'rinadi.
 * - Maqsadlar: `phone_click` (har qanday tel: havola), `telegram_click`
 *   (t.me havolalari), `lead_sent` (JS'siz
 *   forma /rahmat/ ga tushadi; JS bilan yuborilsa LeadForm o'zi yuboradi).
 */
const METRIKA_SCRIPT = `(function(){
if (window.__hsRedirecting) return;
var h = location.hostname;
if (h !== 'hamkorsavdo.uz' && h !== 'www.hamkorsavdo.uz' && location.search.indexOf('_ym_debug') < 0) return;
var ref = document.referrer;
try { var s = sessionStorage.getItem('hs_ref'); if (s !== null) { ref = s; sessionStorage.removeItem('hs_ref'); } } catch(e){}
(function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};m[i].l=1*new Date();
for (var j=0;j<document.scripts.length;j++){if(document.scripts[j].src===r){return;}}
k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)})
(window,document,'script','https://mc.yandex.ru/metrika/tag.js?id=${YM_ID}','ym');
ym(${YM_ID}, 'init', {ssr:true, webvisor:true, clickmap:true, referrer:ref, url:location.href, accurateTrackBounce:true, trackLinks:true});
document.addEventListener('click', function(ev){
  var a = ev.target && ev.target.closest && ev.target.closest('a[href]');
  if (!a) return;
  var href = a.getAttribute('href') || '';
  if (href.indexOf('tel:') === 0) ym(${YM_ID}, 'reachGoal', 'phone_click');
  else if (/^https:\\/\\/t\\.me\\//.test(href)) ym(${YM_ID}, 'reachGoal', 'telegram_click');
}, true);
if (location.pathname.indexOf('/rahmat') >= 0) ym(${YM_ID}, 'reachGoal', 'lead_sent');
})();`;

/**
 * Mijoz saytga qayerdan kelgani — birinchi kirishda bir marta aniqlanadi
 * (utm_source ustun), sessiya davomida eslab qolinadi va ariza yuborilganda
 * formaning `src` maydoniga yoziladi. Admin panel "qaysi manba ariza
 * keltiradi" hisobotini shundan tuzadi.
 */
const SOURCE_SCRIPT = `(function(){try{
var src = sessionStorage.getItem('hs_src');
if (!src) {
  var q = /[?&]utm_source=([^&#]+)/.exec(location.search);
  if (q) { src = decodeURIComponent(q[1]); }
  else {
    var r = sessionStorage.getItem('hs_ref'); if (r === null) r = document.referrer;
    var host = ''; try { host = new URL(r).hostname.replace(/^www\\./, ''); } catch(e) {}
    if (!host || host === location.hostname.replace(/^www\\./, '')) src = 'togridan';
    else if (/instagram\\.com$/.test(host)) src = 'instagram';
    else if (/(^|\\.)t\\.me$|telegram/.test(host)) src = 'telegram';
    else if (/(^|\\.)google\\./.test(host)) src = 'google';
    else if (/(^|\\.)yandex\\./.test(host) || /(^|\\.)ya\\.ru$/.test(host)) src = 'yandex';
    else if (/facebook\\.com$/.test(host)) src = 'facebook';
    else src = host;
  }
  sessionStorage.setItem('hs_src', String(src).toLowerCase().slice(0, 60));
}
document.addEventListener('submit', function(ev){
  var f = ev.target && ev.target.querySelector && ev.target.querySelector('input[name="src"]');
  if (f) f.value = sessionStorage.getItem('hs_src') || '';
}, true);
}catch(e){}})();`;

export default function RootLayout({
  children,
}: Readonly<{ children: React.ReactNode }>) {
  return (
    <html lang="uz" className={`${outfit.variable} ${bebas.variable}`}>
      <head>
        <script data-keep dangerouslySetInnerHTML={{ __html: LANG_DETECT_SCRIPT }} />
        <script data-keep dangerouslySetInnerHTML={{ __html: SOURCE_SCRIPT }} />
        <script data-keep dangerouslySetInnerHTML={{ __html: METRIKA_SCRIPT }} />
      </head>
      <body>
        <noscript>
          <div>
            <img
              src={`https://mc.yandex.ru/watch/${YM_ID}`}
              style={{ position: "absolute", left: "-9999px" }}
              alt=""
            />
          </div>
        </noscript>
        {/* Reveal animations never gate content: the document ships fully
            visible and <Reveal> opts individual blocks into motion at runtime,
            so no-JS and older browsers simply read the page as-is. */}
        <Reveal />
        <MetrikaPageview />
        {children}
      </body>
    </html>
  );
}
