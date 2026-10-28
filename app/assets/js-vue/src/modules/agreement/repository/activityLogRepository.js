import axios from 'axios';

export function fetchActivityLogsForAgreement(agreementId, { page = 1, pageSize = 8 } = {}) {
    return axios.get(`/agreement/${agreementId}/activity-log`, {
        params: { page, pageSize },
    });
}

export function fetchActivityLogsForAgreementLine(agreementLineId, { page = 1, pageSize = 8 } = {}) {
    return axios.get(`/agreement-line/${agreementLineId}/activity-log`, {
        params: { page, pageSize },
    });
}
