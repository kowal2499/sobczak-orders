import axios from 'axios';

export function fetchViews(listingId) {
    return axios.get(`/user-settings/${listingId}`);
}

export function saveViews(listingId, data) {
    return axios.put(`/user-settings/${listingId}`, { data });
}