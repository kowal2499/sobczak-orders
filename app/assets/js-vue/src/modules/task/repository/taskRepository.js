import axios from 'axios';

export function updateTask(id, payload) {
    return axios.put(`/tasks/${id}`, payload);
}

