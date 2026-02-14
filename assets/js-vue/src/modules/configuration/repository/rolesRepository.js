import axios from "axios";

export function fetchRolesByUserId(userId) {
    return axios.get(`/authorization/role/user/${userId}`);
}

export function assignRoles(userId, roles) {
    return axios.post(`/authorization/role/user/${userId}/assign`, { roles });
}

export function fetchGrantRoleValues() {
    return axios.get('/authorization/grant/role/value');
}

export function mergeRoles(roleIds) {
    return axios.post('/authorization/grant/role/value/merge', { roles: roleIds });
}

