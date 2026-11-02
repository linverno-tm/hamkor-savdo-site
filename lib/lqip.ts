import type { CSSProperties } from "react";

/**
 * Surat "muqovalari" (LQIP) — 24 px kenglikdagi xira nusxa, HTML ichida (data: URI).
 * Katta surat kelguncha idish foni bo'lib turadi: bo'sh quti o'rniga rasmning
 * ranglari darhol ko'rinadi. Chrome past entropiyali rasmni LCP deb hisoblamaydi.
 * Yaratish: 02.11, PIL (24 px, webp q40). Surat almashsa — qayta yarating.
 */
export const LQIP: Record<string, string> = {
  xona: "data:image/webp;base64,UklGRsQAAABXRUJQVlA4ILgAAABwBQCdASoYABIAPu1ur1IppiQiqAgBMB2JQBajUAA1Q7qo5lk1DH15/PVjS6/ifFvLnXYAAP7qgnvp23Jh8JRcKGeWGend/T4jWU43SSS51NNvLIe1Ul1NNbOv5toZfqmrS1tOSKqwCHmFGhCrthGDg0CgH4vQnKbXKMB96fRNWKkxs0fmiLooqP/HSPeMad1WUhaDWaAd/Rm3v+benskW3N9t+Lih9V+7OoLGFVjFeUABRliR6AAA",
  texnika: "data:image/webp;base64,UklGRroAAABXRUJQVlA4IK4AAADQBACdASoYABIAPu1sqlEppaOiqAqpMB2JZwDLpAiWLhCx85+552gquvLbqDfsgADfs5dfpQQ/i0A9dZ5fE6UhUQ97wccIZOqwJorKul1wNRygSE5cQIHtQRTF+DS9JC4juulFR0JfeBsCidqABdZ87WydkWdaU65AFglaZeMo0qNgP++uPycxgABiAOqXtP/E2jtFAHxWVkF8cIXKkhbEaDS/o1H6Au+pG8TgAAA=",
  mebel: "data:image/webp;base64,UklGRqoAAABXRUJQVlA4IJ4AAAAwBQCdASoYABIAPu1ur1IppiQiqAgBMB2JZwAAL+sVHSUfWv3Xf8kZ3rdrfRSg0Zu1AAD+wG3KWB0QwFRe6IaOxQI/nKc/mSdkDKp9aq6iOJGjDq7AVjViBjV6oVWOBW4hkT3352KejIITzqJaMSZVTIVJk1RJau9D+KaCNSfZFiUQXoZuWGqtYlxhRLEq7r3yfmTGxb18rubIwAAAAA==",
  tilla: "data:image/webp;base64,UklGRrQAAABXRUJQVlA4IKgAAABwBQCdASoYABIAPu1ur1IppiQiqAgBMB2JYwDKtdwAXB6GrcsEfbRuN5LCSw0hBfIbu77gAPsFKzU6DaRcKMWCEtwfxkuJxnaDVzIbolAUzohMPUQixZlpwb9g96kbkIgWJt4+6DZKL40VCaCQhhK65kwi1q61rTushv4WsVDdGtJ346yHr9ofI0KSGtJiHGz3LzNeZthxhsAbhnzGF2wNTdqYVQOaOAA=",
  skuter: "data:image/webp;base64,UklGRvQAAABXRUJQVlA4IOgAAADQBQCdASoYABIAPu1sqlGppaOiqAqpMB2JYwDHMYv+aM0+e0AKpuJVASu+d/kSlXR3rseEhpQAAPzquNYzjpVYut9yhrwwTmeRkNfleYOg8sCteCeqocT03S/jr8gdIVG/7eRCHwq/bdFvxhgcNJk9sRS5un3Guvr5dp1lEuLVzYQ+K18Izeonp2ftgx3xfdtpmYSRQqsSsxw6Q1Vp47qkBSG63qjKT+IMYYnaifIpvWJasdboL/nciKriBXe/K/NHm82bZRq/0f2JkzubGKJDxfhn1uGLBmSdZOSf7aBZJz+Pzqk0AAAA",
  ozodbek: "data:image/webp;base64,UklGRo4AAABXRUJQVlA4IIIAAABQBACdASoYAA4APu1iqU2ppaOiMAgBMB2JYwC7MoACEi/CNpVPza8Odb5gAPaCxqVSoCVhrW0NmLiwjRpy2Sg51kRM+UqA99GpCrepGkF1t6sXYc5Vx4FthhWcNnKAGI56x0jkX1H7DEZLJkZ5tXdvee/dFlDpkqoj078KiipewAAA",
};
/** Idish uchun inline uslub: xira muqova fon bo'lib cho'ziladi. */
export function lqipStyle(key: string | undefined): CSSProperties | undefined {
  const u = key ? LQIP[key] : undefined;
  return u ? { background: `center / cover no-repeat url(${u})` } : undefined;
}
