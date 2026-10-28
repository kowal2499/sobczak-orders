import axios from 'axios'

export function fetchPeriods() {
    return axios.get('/bonus-settlement/periods')
}

export function fetchPeriod(id) {
    return axios.get(`/bonus-settlement/periods/${id}`)
}

export function fetchPeriodLogs(id, { page = 1, pageSize = 10 } = {}) {
    return axios.get(`/bonus-settlement/periods/${id}/logs`, { params: { page, pageSize } })
}

export function createPeriod(year, month) {
    return axios.post('/bonus-settlement/periods', { year, month })
}

export function recalculatePeriod(id) {
    return axios.post(`/bonus-settlement/periods/${id}/recalculate`, {})
}

export function resetPeriodAdjustments(id) {
    return axios.post(`/bonus-settlement/periods/${id}/reset-adjustments`)
}

export function closePeriod(id) {
    return axios.post(`/bonus-settlement/periods/${id}/close`)
}

export function reopenPeriod(id) {
    return axios.post(`/bonus-settlement/periods/${id}/reopen`)
}

/**
 * @param factorsAdjusted null zdejmuje korektę i przywraca wartość wyliczoną
 */
export function adjustEntry(entryId, factorsAdjusted, note) {
    return axios.put(`/bonus-settlement/entries/${entryId}`, { factorsAdjusted, note })
}
