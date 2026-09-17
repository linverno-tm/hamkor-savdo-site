"use client";

import { useEffect, useRef } from "react";
import { usePathname } from "next/navigation";
import { ym } from "@/lib/metrika";

/**
 * Next <Link> sahifani qayta yuklamasdan almashtiradi, shuning uchun Metrika
 * buni o'zi ko'rmaydi. Birinchi yuklanishni `init` hisoblaydi, keyingi har
 * bir o'tishni shu yerda `hit` qilib yuboramiz.
 */
export function MetrikaPageview() {
  const pathname = usePathname();
  const prevUrl = useRef<string | null>(null);

  useEffect(() => {
    const url = window.location.href;
    if (prevUrl.current !== null) ym("hit", url, { referer: prevUrl.current });
    prevUrl.current = url;
  }, [pathname]);

  return null;
}
