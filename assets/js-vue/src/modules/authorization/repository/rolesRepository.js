import axios from "axios";

export function fetchRolesByUserId(userId) {
    return axios.get(`/authorization/role/user/${userId}`);
}

export function assignRoles(userId, roles) {
    return axios.post(`/authorization/role/user/${userId}/assign`, { roles });
}

export function fetchRoles() {
    return axios.get('/authorization/role/list');
}

