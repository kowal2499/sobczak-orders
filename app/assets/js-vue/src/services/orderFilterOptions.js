import Vue from "vue";
import api from "@/api/neworder";

/**
 * Podpowiedzi dla filtrów klienta i autora. Wspólne dla panelu filtrów i chipów,
 * które zamieniają zapisane w widoku id na nazwy.
 */
export const filterOptions = Vue.observable({
  customers: [],
  authors: [],
})

let loading = null

export function loadFilterOptions() {
  if (!loading) {
    loading = api.fetchOrdersFilterOptions()
      .then(({ data }) => {
        filterOptions.customers = data.customers || []
        filterOptions.authors = data.authors || []
      })
      .catch(() => {
        loading = null
      })
  }

  return loading
}

/**
 * @param {Array<{id: number, name: string}>} options
 * @param {number[]} ids
 * @returns {string}
 */
export function namesOf(options, ids) {
  return (ids || [])
    .map(id => (options.find(option => option.id === id) || {}).name || `#${id}`)
    .join(', ')
}
