import { reactive, readonly } from 'vue'

/**
 * Carries the donor's reference from registration into booking.
 *
 * Deliberately in-memory only. A donor reference is private information, so it
 * is never written to localStorage, sessionStorage, or a URL — a shared or
 * restored browser must not reveal it. The cost is that a page refresh clears
 * it, which is the correct trade for this data.
 *
 * A plain reactive module rather than Pinia: this is two fields with no
 * actions, and a store dependency would not earn its place.
 */
const state = reactive({
  donorReference: null,
  donorPhone: null,
})

export const journey = readonly(state)

export function rememberDonor({ reference, phone }) {
  state.donorReference = reference ?? null
  state.donorPhone = phone ?? null
}

export function clearJourney() {
  state.donorReference = null
  state.donorPhone = null
}
