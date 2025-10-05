import axios from "axios";

export function setGrantRoleValue(data) {
	return axios.post('/authorization/grant/role/value', data);
}

export function fetchGrantRoleValues() {
	return axios.get('/authorization/grant/role/value');
}

export function setGrantUserValues(userId, data) {
    return axios.post(`/authorization/grant/user/${userId}/values`, data);
}

export function fetchGrantUserValues(userId) {
	return axios.get(`/authorization/grant/user/value/${userId}`);
}


