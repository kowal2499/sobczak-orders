import axios from 'axios';
import Routing from './routing';

export default {
    fetchUsers() {
        return axios.post(Routing.get('security_fetch'));
    }
}
