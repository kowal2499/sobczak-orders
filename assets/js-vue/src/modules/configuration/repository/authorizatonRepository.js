import axios from "axios";

export function fetchGrants() {
    return axios.get('/authorization/grant/list');
}

export function createRole(name) {
    return axios.post('/authorization/role', { name });
}

export function fetchModules() {
    return axios.get('/module/list');
}

export function fetchRoles() {
    return axios.get('/authorization/role/list');
}

export function setGrantUserValues(userId, data) {
    return axios.post(`/authorization/grant/user/${userId}/values`, data);
}

export function setGrantRoleValue(data) {
    return axios.post('/authorization/grant/role/value', data);
}

export function fetchGrantUserValues(userId) {
    return axios.get(`/authorization/grant/user/value/${userId}`);
}