import axios from "axios";

export function fetchHolidays(startDate, endDate) {
    const params = new URLSearchParams()
    params.append('startDate', startDate)
    params.append('endDate', endDate)
    return axios.get(`/reports/schedule/holidays?${params.toString()}`)
}

export function fetchCapatity(startDate, endDate) {
    const params = new URLSearchParams()
    params.append('startDate', startDate)
    params.append('endDate', endDate)
    return axios.get(`/reports/schedule/capacity?${params.toString()}`)
}

export function fetchAgreementLines(startDate, endDate) {
    const params = new URLSearchParams()
    params.append('startDate', startDate)
    params.append('endDate', endDate)
    return axios.get(`/reports/schedule/agreement-lines?${params.toString()}`)
}
