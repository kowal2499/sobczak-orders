import axios from "axios";

export function fetchFactors(id) {
    return axios.get(`/production/factor/${id}`);
}

export function storeFactors(id, data) {
    return axios.post(`/production/factor/${id}`, data);
}