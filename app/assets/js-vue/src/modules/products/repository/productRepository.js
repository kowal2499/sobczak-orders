import axios from 'axios'

export function fetchProducts({ q = '', page = 1 } = {}) {
    return axios.get('/api/products', { params: { q, page } })
}

export function fetchProduct(id) {
    return axios.get(`/api/products/${id}`)
}

export function createProduct(payload) {
    return axios.post('/api/products', payload)
}

export function updateProduct(id, payload) {
    return axios.put(`/api/products/${id}`, payload)
}
