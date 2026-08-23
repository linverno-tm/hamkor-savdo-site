/**
 * Group thousands with a plain space.
 *
 * Deliberately hand-rolled rather than `Intl.NumberFormat("uz-UZ")`: Node and
 * the browser ship different ICU data for that locale, which makes the server
 * and client disagree on the separator. A space is also the correct separator
 * in this market.
 */
export function groupDigits(n: number): string {
  return String(n).replace(/\B(?=(\d{3})+(?!\d))/g, " ");
}
