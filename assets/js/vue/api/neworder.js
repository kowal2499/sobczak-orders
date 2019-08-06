import axios from 'axios';

import Routing from './routing';

export default {

    findCustomers(q) {

        return axios.get(Routing.get('customers_search'), {
                params: {
                    q: q
                }
            }
        );
    },

    fetchProducts() {
        return axios.get(Routing.get('products_fetch'));
    },

    fetchAgreements(search) {
        return axios.post(Routing.get('agreements_fetch'), { search });
    },

    archiveAgreement(agreementId) {
        return axios.post(Routing.get('agreement_line_archive') + '/' + agreementId);
    },

    updateOrder(lineId, productionData, agreementLineData) {
        return axios.post(Routing.get('agreement_line_update') + '/' + lineId, { productionData, agreementLineData });
    },

    storeOrder(customerId, products, orderNumber) {
        return axios.post(Routing.get('orders_add'), { customerId, products, orderNumber });
    },

    storeProductionPlan(plan, orderLineId) {
        return axios.post(Routing.get('production_save'), { plan, orderLineId });
    },

    getNumber(customerId) {
        return axios.post(Routing.get('orders_number') + '/' + customerId);
    },

    validateNumber(number) {
        return axios.post(Routing.get('validate_number') + '/' + number);
    }




}