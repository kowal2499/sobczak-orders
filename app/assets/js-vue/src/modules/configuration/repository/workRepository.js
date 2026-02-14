import axios from "axios";

export function saveCapacity(payload) {
    return axios.post('/work-configuration/capacity', payload);
}

export function fetchCapacities(startDate = null, endDate = null) {
    const params = new URLSearchParams();
    if (startDate) {
        params.append('startDate', startDate);
    }
    if (endDate) {
        params.append('endDate', endDate);
    }

    return axios.get(`/work-configuration/capacity?${params.toString()}`);
}

export function deleteCapacity(id) {
    return axios.delete(`/work-configuration/capacity/${id}`);
}

export function fetchHolidayEvents(startDate, endDate) {
    const params = new URLSearchParams()
    params.append('startDate', startDate)
    params.append('endDate', endDate)
    params.append('type', 'holiday')
    return axios.get(`/work-configuration/schedule?${params.toString()}`)
}

export function saveSchedule(payloadCollection) {
    return Promise.all(payloadCollection.map(payload =>
        axios.post('/work-configuration/schedule', payload))
    )
}

export function deleteSchedule(id) {
    return axios.delete(`/work-configuration/schedule/${id}`);
}