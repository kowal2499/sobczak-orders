import axios from 'axios';
import Routing from './routing';

export default {
    fetchUsers(showInactive) {
        return axios.get(Routing.get('users_fetch') + `?all=${showInactive ? 'true' : 'false'}`);
    },

    fetchUser(id) {
        return axios.get(Routing.get('user_fetch') + '/' + id);
    },

    addUser(user) {
        return axios.post(Routing.get('user_add'), user);
    },

    storeUser(user) {
        return axios.patch(Routing.get('user_edit') + '/' + user.id, user);
    }
}
