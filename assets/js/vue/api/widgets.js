import axios from 'axios';
import Routing from './routing';

export default {

    productionSummary(month, year) {
        return axios.post(Routing.get('production_summary'), {month, year});
    }

}