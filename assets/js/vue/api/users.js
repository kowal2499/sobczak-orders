import axios from 'axios';
import Routing from './routing';

export default {
    fetchUsers() {
        return axios.post(Routing.get('security_fetch'));
    },

    fetchUser(id) {
        return axios.post(Routing.get('security_fetch_user') + '/' + id);
    },

    storeUser(user) {
        return axios.post(Routing.get('security_store_user'), { ... user });
    }
}
