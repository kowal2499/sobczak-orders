import axios from 'axios';

export function findTasks(params) {
    return axios.get('/tasks/find', { params });
}

export function createTask(payload) {
    return axios.post('/tasks', payload);
}

export function updateTask(id, payload) {
    return axios.put(`/tasks/${id}`, payload);
}

export function updateTaskStatus(id, status) {
    return axios.post(`/tasks/${id}/status`, { status });
}

export function deleteTask(id) {
    return axios.delete(`/tasks/${id}`);
}
