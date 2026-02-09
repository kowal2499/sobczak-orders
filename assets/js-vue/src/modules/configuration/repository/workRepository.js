import axios from "axios";

export function saveCapacity(payload) {
    return axios.post('/work-configuration/capacity', payload);
}

export function fetchCapacities(startDate = null, endDate = null) {
    const params = new URLSearchParams();
    if (startDate) {
        params.append('startDate', startDate);
    }
    if (endDate) {
        params.append('endDate', endDate);
    }

    return axios.get(`/work-configuration/capacity?${params.toString()}`);
}

export function deleteCapacity(id) {
    return axios.delete(`/work-configuration/capacity/${id}`);
}