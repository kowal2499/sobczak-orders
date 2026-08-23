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
