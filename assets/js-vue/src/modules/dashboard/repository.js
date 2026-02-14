import axios from "axios";

/**
 * @deprecated
 */
export function getAgreementLines(start, end, departments) {
    return axios.get(`/api/reports/agreement-line-production`, {params: {start, end, departments}});
}

export function getAgreementLinesSummary(start, end) {
    return axios.get(`/reports/production/agreement-line-production-summary`, {params: {start, end}});
}

export function getProductionTasksCompletionSummary(start, end) {
    return axios.get(`/reports/production/production-tasks-completion-summary`, {params: {start, end}});
}


export function getOldSummary(start, end) {
    return axios.post(`production/summary`, {
        month: (new Date(start)).getMonth() + 1,
        year: (new Date(end)).getFullYear()
    });
}

export function getProductionPendingDetails(start, end) {
    return axios.get(`/reports/production/production-pending-details`, {params: {start, end}});
}

export function getProductionFinishedDetails(start, end) {
    return axios.get(`/reports/production/production-finished-details`, {params: {start, end}});
}

export function getDepartmentsCapacity(start, end) {
    return axios.get(`/reports/production/production-capacity`, {params: {start, end}});
}
