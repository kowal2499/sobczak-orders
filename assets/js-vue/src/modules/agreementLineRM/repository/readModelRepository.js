import axios from 'axios';

export function rmSearch(search) {
    return axios.post('/agreement-line/rm/search', { search });
}

export function rmFetchSingle(agreementLineId) {
    return axios.get(`/agreement-line/rm/${agreementLineId}`);
}