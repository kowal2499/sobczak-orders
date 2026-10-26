import axios from 'axios'

export function fetchHistory(from, to) {
    return axios.get('/metric-snapshot/history', { params: { from, to } })
}

export function captureToday() {
    return axios.post('/metric-snapshot/capture')
}
