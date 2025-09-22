import axios from "axios";

export function setGrantRoleValue(data) {
	return axios.post('/authorization/grant/role/value', data);
}

export function fetchGrantRoleValues() {
	return axios.get('/authorization/grant/role/value');
}

export function createGrantUserValue(data) {
    return Promise.resolve()
}

export function updateGrantUserValue(data) {
    return Promise.resolve()
}

export function fetchGrantUserValues(userId) {
	return axios.get(`/authorization/grant/user/value/${userId}`);
}

export function deleteGrantUserValue({ userId, grantId, optionSlug}) {
    return axios.delete('/authorization/grant/user/value', { userId, grantId, optionSlug });
}


