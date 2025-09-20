import axios from "axios";

export function setGrantRoleValue(data) {
	return axios.post('/authorization/grant/role/value', data);
}

export function fetchGrantRoleValues() {
	return axios.get('/authorization/grant/role/value');
}

export function fetchGrantUserValues() {
	return Promise.resolve()
}

export function setGrantUserValue(data) {
	return Promise.resolve()
}

