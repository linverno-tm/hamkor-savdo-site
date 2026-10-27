"use client";

import { useEffect } from "react";
import { usePathname } from "next/navigation";

/**
 * <html lang> ni sahifa yo'liga moslaydi. Statik HTML'da buni postbuild
 * (tools/uz-kr-build.mjs) qiladi, lekin Next <Link> bilan /ru/ ga sahifani
 * qayta yuklamasdan o'tilganda root layout'dagi lang="uz" qolib ketardi:
 * ekran o'quvchi ruscha matnni o'zbekcha o'qiydi, kirill shrifti
 * (app/globals.css > :root:lang) ham yoqilmaydi.
 */
export function LangSync() {
  const pathname = usePathname();

  useEffect(() => {
    // usePathname emas, manzil qatori: /uz-kr/ nusxasi lotin sahifaning RSC
    // ma'lumoti bilan ochiladi va router yo'lni "/" deb biladi.
    const path = window.location.pathname;
    const lang = /^\/ru(\/|$)/.test(path) ? "ru" : /^\/uz-kr(\/|$)/.test(path) ? "uz-Cyrl" : "uz";
    if (document.documentElement.lang !== lang) document.documentElement.lang = lang;
  }, [pathname]);

  return null;
}
