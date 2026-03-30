import axios from 'axios';

export function updateTask(id, payload) {
    return axios.put(`/tasks/${id}`, payload);
}

export function updateTaskStatus(id, status) {
    return axios.post(`/tasks/${id}/status`, { status });
}
