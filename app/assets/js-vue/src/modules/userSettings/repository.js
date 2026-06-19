import axios from "axios";

export function getUserSetting(context) {
    return axios.get(`/user-settings/${context}`);
}

export function saveUserSetting(context, data) {
    return axios.put(`/user-settings/${context}`, { data });
}
