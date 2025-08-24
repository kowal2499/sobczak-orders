import axios from "axios";

export function fetchGrants() {
    return axios.get('/authorization/grant/list');
}

export function fetchRoles() {
    return axios.get('/authorization/role/list');
}

export function fetchModules() {
    return axios.get('/module/list');
}

export function setGrantRoleValue(data) {
    return axios.post('/authorization/grant/role/value', data);
}

