import axios from 'axios';
import Routing from './routing';

export default {
    fetchProduction(search) {
        return axios.post(Routing.get('production_fetch'), {search});
    },

    updateStatus(productionId, newStatus) {
        return axios.post(Routing.get('production_status_update'), {productionId, newStatus});
    }
}