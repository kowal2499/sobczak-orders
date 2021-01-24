import axios from "axios";

export function fetchAll() {
    return axios.get(`/api/tag-definition`);
}

export function update(id, payload) {
    return axios.put(`/api/tag-definition/${id}`, payload);
}

export function create(payload) {
    return axios.post(`/api/tag-definition`, payload);
}

export function deleteTag(id) {
    return axios.delete(`/api/tag-definition/${id}`);
}