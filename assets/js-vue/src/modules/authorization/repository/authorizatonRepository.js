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
