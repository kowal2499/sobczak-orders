<template>
    <div>

        <filters
            :filters-collection="filters"
            @filtersChange="handleFiltersChange"
        >

            <div class="col float-right text-right">
                <a :href="newOrderLink" class="btn btn-success text-right mb-4"><i class="fa fa-plus" aria-hidden="true"></i> Nowe zamówienie</a>
            </div>

        </filters>

        <production-plan 
            :showModal="showProductionPlan" 
            :departments="departments"
            :production="selectedProduction"
            :orderContext="selectedOrder"
            @closeModal="closePlan($event)" 
        />

        <br>

        <div class="loading text-center" v-if="loading">
            <i class="fa fa-spinner fa-pulse fa-2x fa-fw"></i>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Data otrzymania</th>
                    <th>Data dostawy</th>
                    <th>Klient</th>
                    <th>Handlowiec</th>
                    <th>Produkt</th>
                    <th>Produkcja</th>
                    <th>Akcje</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(agreement, key) in agreements" :key="key">
                    <td>{{ agreement.header.orderNumber || agreement.line.id }}</td>
                    <td>{{ agreement.header.createDate }}</td>
                    <td>{{ agreement.line.confirmedDate }}</td>
                    <td>{{ customerName(agreement.customer) }}</td>
                    <td></td>
                    <td>{{ agreement.product.name }}</td>
                    <td>
                        <span v-if="agreement.production && agreement.production.data.length === 0" class="badge badge-pill badge-danger">Nie zlecone</span>

                        <span v-if="agreement.production && agreement.production.data[4] && agreement.production.data[4].status === 3" class="badge badge-pill badge-success">Zakończona</span>
                        <span v-else-if="agreement.production && agreement.production.data.length > 0" class="badge badge-pill badge-primary">W trakcie</span>
                    </td>
                    <td>
                        <dropdown>
                            <a class="dropdown-item" :href="getRouting().get('agreement_line_details') + '/' + agreement.line.id">
                                <i class="fa fa-tasks" aria-hidden="true"></i> Panel
                            </a>

                            <a class="dropdown-item" :href="getRouting().get('orders_edit') + '/' + agreement.header.id">
                                <i class="fa fa-pencil" aria-hidden="true"></i> Edycja
                            </a>

                            <a class="dropdown-item" href="#"
                               v-if="agreement.production && agreement.production.data.length === 0"
                               @click="handleRunProduction(agreement)"
                            >
                                <i class="fa fa-cogs" aria-hidden="true"></i> Przekaż na produkcję
                            </a>

                            <a class="dropdown-item text-danger" href="#" @click.prevent="confirmDeleteModal(agreement)">
                                <i class="fa fa-trash text-danger" aria-hidden="true"></i> Usuń
                            </a>

                        </dropdown>
                    </td>
                </tr>
            </tbody>
        </table>

        <confirmation-modal
            :show="confirmations.delete.show"
            :busy="confirmations.delete.busy"
            @answerYes="handleDelete(confirmations.delete.context)"
            @closeModal="confirmations.delete.show = false"
            v-if="confirmations.delete.show"
        >
            <div>
                <p><strong>Czy na pewno usunąć zlecenie numer '{{ confirmations.delete.context.header.orderNumber }}'?</strong></p>

                <ul class="list-unstyled">
                    <li v-for="product in getOrderedProducts(confirmations.delete.context)">{{ product.name }}</li>
                </ul>
                <div class="alert alert-danger" v-if="hasProduction(confirmations.delete.context)">
                    <i class="fa fa-exclamation-circle" aria-hidden="true"></i>
                    Usunięcie zamówienia usunie również wszystkie związane z nim działania na produkcji.
                </div>
            </div>

        </confirmation-modal>

    </div>
</template>

<script>
    import qs from 'qs';
    import moment from 'moment';
    import Filters from './Filters';
    import Dropdown from '../../base/Dropdown';
    import ProductionPlan from './production-plan';
    import api from '../../../api/neworder';
    import routing from  '../../../api/routing';
    import ConfirmationModal from "../../base/ConfirmationModal";

    export default {
        name: "OrdersList",

        components: { Filters, ProductionPlan, Dropdown, ConfirmationModal },

        data() {
            return {
                filters: {
                    dateStart: '',
                    dateEnd: '',
                    page: 1,
                    archived: false
                },

                agreements: [],
                departments: [],
                production: [],

                newOrderLink: routing.get('orders_view_new'),

                showProductionPlan: false,
                selectedOrder: null,
                selectedProduction: [],

                confirmations: {
                    delete: {
                        show: false,
                        context: false,
                        busy: false
                    }
                },

                loading: false
            }
        },

        mounted() {
            this.setFilters();

        },

        watch: {

            filters: {
                handler(value) {
                    let queryString = qs.stringify({
                        dateStart: this.filters.dateStart,
                        dateEnd: this.filters.dateEnd,
                        archived: this.filters.archived,
                        page: this.filters.page
                    });

                    if (queryString.length > 0) {
                        queryString = '?'.concat(queryString);  
                        history.pushState(null, '', window.location.pathname + queryString);  
                    }

                    this.fetchData();

                },
                deep: true
            },
        },

        methods: {

            handleDelete(order) {
                this.confirmations.delete.busy = true;

                api.deleteOrder(order.header.id)
                    .then(() => {
                        this.agreements = this.agreements.filter(agreement => {
                            return agreement.header.id !== order.header.id;
                        });
                    })
                    .finally(() => {
                        this.confirmations.delete.busy = false;
                        this.confirmations.delete.show = false;
                    });
            },

            confirmDeleteModal(item) {
                this.confirmations.delete.context = item;
                this.confirmations.delete.show = true;
            },

            setFilters() {
                let query = qs.parse(window.location.search, { ignoreQueryPrefix: true });
                this.filters.dateStart = query.dateStart;
                this.filters.dateEnd = query.dateEnd;
                this.filters.archived = query.archived === 'true';
                this.filters.page = query.page || 1;

                if ((!this.filters.dateStart || !moment(this.filters.dateStart).isValid())) {
                    this.filters.dateStart = moment().startOf('month').format('YYYY-MM-DD');
                }

                if ((!this.filters.dateEnd || !moment(this.filters.dateEnd).isValid())) {
                    this.filters.dateEnd = moment().endOf('month').format('YYYY-MM-DD');
                }
                    
            },

            beforeShowPlan(key) {
                this.selectedOrder = this.agreements[key].line.id;
                this.selectedProduction = this.agreements[key].production.data;
                this.showProductionPlan = true;
            },

            closePlan(event) {
                this.showProductionPlan = false;
                let order = this.agreements.find(item => { return item.line.id === this.selectedOrder; });

                if (event) {

                    order.production.data = event[0];    
                }
                
            },

            handleFiltersChange(date) {
                this.filters = date;
            },

            fetchData() {
                this.loading = true;
                
                api.fetchAgreements(this.filters)
                    .then(data => { 
                        this.agreements = data.data.orders || [];
                        this.departments = data.data.departments || [];
                        this.production = data.data.production.data || [];
                    })
                    .catch(data => {})
                    .finally(() => { this.loading = false; });
            },

            customerName(customer) {

                if (!customer) {
                    return '';
                }
                let result = customer.name;

                if (customer.first_name || customer.last_name) { 
                    result = result + ' (' + [customer.first_name, customer.last_name].join(' ') + ')';
                }
                
                return result;
            },

            prodBtnDescription(key) {
                if (!this.agreements[key]) {
                    return '';
                };

                if (this.agreements[key].production.data.length === 0) {
                    return 'Zleć';
                }
                else {
                    return 'Edytuj';
                }
            },

            prodBtnIcon(key) {
                if (!this.agreements[key]) {
                    return '';
                };

                if (this.agreements[key].production.data.length === 0) {
                    return 'fa fa-list-ol';
                }
                else {
                    return 'fa fa-pencil';
                }
            },

            handleRunProduction(agreement) {

                let production = this.departments.map(department => {
                    return {
                        slug: department.slug,
                        name: department.name,
                        status: 0,
                        dateFrom: null,
                        dateTo: null
                    };
                });


                api.storeProductionPlan(production, agreement.line.id)
                    .then(({data}) => {
                        agreement.production.data = Array.isArray(data) ? data[0] : [];
                    })
                    .finally(() => {

                    })
            },

            hasProduction(agreement) {
                for (let testing of this.agreements) {
                    if (testing.header.id === agreement.header.id) {
                        if (testing.production.data.length) {
                            return true;
                        }
                    }
                }
                return false;
            },

            getOrderedProducts(agreement) {
                let result = [];

                for (let testing of this.agreements) {
                    if (testing.header.id === agreement.header.id) {
                        result.push(testing.product);
                    }
                }

                return result;
            },

            getRouting() {
                return routing;
            }

        }
    }
</script>

<style scoped>
    .table thead th {
        text-align: center;
    }
</style>