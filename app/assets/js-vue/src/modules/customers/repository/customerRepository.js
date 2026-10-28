import axios from 'axios'

export function fetchCustomers({ q = '', page = 1 } = {}) {
    return axios.get('/api/customers', { params: { q, page } })
}

export function fetchCustomer(id) {
    return axios.get(`/api/customers/${id}`)
}

export function fetchCountries() {
    return axios.get('/api/customers/countries')
}

export function createCustomer(payload) {
    return axios.post('/api/customers', payload)
}

export function updateCustomer(id, payload) {
    return axios.put(`/api/customers/${id}`, payload)
}
