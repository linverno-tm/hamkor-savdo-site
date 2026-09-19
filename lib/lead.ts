"use client";

import { useState } from "react";
import { ym } from "@/lib/metrika";

export type LeadStatus =
  | { kind: "idle" | "sending" | "ok" }
  | { kind: "error"; message: string };

/**
 * Ariza formasini yuborish mantiqi — bitta joyda.
 *
 * Sahifada endi ikkita forma bor: bosh qismdagi qisqasi va pastdagi to'lig'i.
 * Ikkalasi ham bir xil manzilga yuboradi, bir xil holatlarni ko'rsatadi va
 * muvaffaqiyatda Metrika'ga `lead_sent` maqsadini yozadi. Shuning uchun bu
 * qism nusxalanmaydi — aks holda biri tuzatilib, ikkinchisi eskirib qolardi.
 *
 * JavaScript bo'lmasa bu umuman ishga tushmaydi: forma oddiy <form> bo'lib
 * qolaveradi va brauzerning o'zi yuboradi, server esa `/rahmat/` ga
 * yo'naltiradi. Shuning uchun `preventDefault` faqat shu yerda chaqiriladi.
 */
export function useLeadSubmit() {
  const [status, setStatus] = useState<LeadStatus>({ kind: "idle" });

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
        ym("reachGoal", "lead_sent");
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

  return { status, onSubmit };
}
