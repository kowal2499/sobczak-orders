import axios from 'axios';

import Routing from './routing';

export default {

    findCustomers(q) {

        return axios.get(Routing.get('api_customers_search'), {
                params: {
                    q: q
                }
            }
        );
    },

    /**
     * Zwraca dane pojedyńczego klienta
     * @param id
     * @returns {Promise<AxiosResponse<T>>}
     */
    fetchCustomer(id) {
        return axios.post(Routing.get('customers_single_fetch') + '/' + id);
    },

    fetchProducts() {
        return axios.get(Routing.get('products_fetch'));
    },

    fetchAgreements(search) {
        return axios.post(Routing.get('agreements_fetch'), { search });
    },

    setAgreementStatus(agreementId, statusId) {
        return axios.post(Routing.get('agreement_line_archive') + '/' + agreementId + '/' + statusId);
    },

    deleteAgreementLine(agreementLineId) {
        return axios.post(Routing.get('agreement_line_delete') + '/' + agreementLineId);
    },

    updateOrder(lineId, productionData, agreementLineData) {
        return axios.post(Routing.get('agreement_line_update') + '/' + lineId, { productionData, agreementLineData });
    },

    storeOrder(customerId, products, orderNumber) {
        return axios.post(Routing.get('orders_add'), { customerId, products, orderNumber });
    },

    patchOrder(agreementId, customerId, products, orderNumber) {
        return axios.post(Routing.get('orders_patch') + '/' + agreementId, { customerId, products, orderNumber });
    },

    deleteOrder(agreementId) {
        return axios.post(Routing.get('orders_delete') + '/' + agreementId);
    },

    /**
     * Zwraca dane pojedyńczego zamówienia
     * @param id
     * @returns {Promise<AxiosResponse<T>>}
     */
    fetchSingleOrder(id) {
        return axios.post(Routing.get('orders_single_fetch') + '/' + id);
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