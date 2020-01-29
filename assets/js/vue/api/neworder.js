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

    updateOrder(lineId, payload) {
        return axios.put(Routing.get('agreement_line_update') + '/' + lineId, payload);
    },

    /**
     * Argumenty jako obiekt FormData (mogą zawierać pliki)
     * @returns {Promise<AxiosResponse<T>>}
     * @param formData
     */
    storeOrder(formData) {
        return axios.post(Routing.get('orders_add'), formData);
    },

    /**
     * Argumenty jako obiekt FormData (mogą zawierać pliki)
     *
     * @param agreementId
     * @param formData
     * @returns {Promise<AxiosResponse<T>>}
     */
    patchOrder(agreementId, formData) {
        return axios.post(Routing.get('orders_patch') + '/' + agreementId, formData);
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
        return axios.post(Routing.get('validate_number'), {number} );
    }

}