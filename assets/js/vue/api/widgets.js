import axios from 'axios';
import Routing from './routing';

export default {

    productionSummary(month, year) {
        return axios.post(Routing.get('production_summary'), {month, year});
    },

    factorsSummary(month, year) {
        return axios.post(Routing.get('factors_summary') + `/${year}-${month}`);
    },

    /**
     * Zwraca liczbę zamówień według statusów
     *
     * @returns {Promise<AxiosResponse<T>>}
     */
    ordersCount() {
        return axios.post(Routing.get('api_fetch_orders_count'))
    }

}