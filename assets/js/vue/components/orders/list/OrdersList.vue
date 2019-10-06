<template>
    <div>

        <filters
            :filters-collection="filters"
            @filtersChange="handleFiltersChange"
        >
            <div class="col float-right text-right">
                <a :href="newOrderLink" class="btn btn-success btn-sm text-right mb-4"><i class="fa fa-plus" aria-hidden="true"></i> Nowe zamówienie</a>
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

        <table-plus :headers="tableHeaders" :loading="loading" :initialSort="'l.confirmedDate'" @sortChanged="updateSort">
            <tr v-for="(agreement, key) in agreements" :key="key">
                <td>{{ agreement.header.orderNumber || agreement.line.id }}</td>
                <td>{{ agreement.header.createDate }}</td>
                <td>{{ agreement.line.confirmedDate }}</td>
                <td>{{ customerName(agreement.customer) }}</td>
                <td>{{ agreement.product.name }}</td>
                <td><span class="badge" :class="getAgreementStatusClass(agreement.line.status)">{{ getAgreementStatusName(agreement.line.status) }}</span></td>
                <td>
                    <span class="badge badge-pill" :class="getProductionStatusData(agreement.production)['className']">
                        {{ getProductionStatusData(agreement.production)['title'] }}
                    </span>
                </td>
                <td>
                    <dropdown class="icon-only">
                        <a class="dropdown-item" :href="getRouting().get('agreement_line_details') + '/' + agreement.line.id">
                            <i class="fa fa-tasks" aria-hidden="true"></i> Panel
                        </a>

                        <a class="dropdown-item" :href="getRouting().get('orders_edit') + '/' + agreement.header.id">
                            <i class="fa fa-pencil" aria-hidden="true"></i> Edycja
                        </a>

                        <a class="dropdown-item" href="#"
                           v-if="agreement.production && agreement.production.data.length === 0 && canUserStartProduction()"
                           @click="handleRunProduction(agreement)"
                        >
                            <i class="fa fa-cogs" aria-hidden="true"></i> Przekaż na produkcję
                        </a>

                        <a class="dropdown-item text-danger" href="#" @click.prevent="confirmDeleteModal(agreement)" v-if="canUserDeleteOrder()">
                            <i class="fa fa-trash text-danger" aria-hidden="true"></i> Usuń
                        </a>

                    </dropdown>
                </td>
            </tr>
        </table-plus>

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
    import TablePlus from '../../base/TablePlus';

    export default {
        name: "OrdersList",

        components: { Filters, ProductionPlan, Dropdown, ConfirmationModal, TablePlus },

        props: {
            statuses: {
                type: Object,
                default: () => {}
            },
            status: {
                default: 0
            }
        },

        data() {
            return {
                filters: {
                    dateStart: {
                        start: '',
                        end: ''
                    },
                    q: '',
                    page: 1,

                    meta: {
                        sort: 'l.confirmedDate:ASC',
                        page: 1
                    }
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

                    let query = {
                        dateStart: this.filters.dateStart.start,
                        dateEnd: this.filters.dateStart.end,
                        page: this.filters.page
                    };

                    if (this.filters.q && this.filters.q.length > 0) {
                        query.q = this.filters.q;
                    }

                    let queryString = qs.stringify(query);

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
                this.filters.dateStart.start = query.dateStart || '';
                this.filters.dateStart.end = query.dateEnd || '';
                this.filters.page = query.page || 1;
                this.filters.q = query.q || '';

                if ((!this.filters.dateStart.start || !moment(this.filters.dateStart.start).isValid())) {
                    this.filters.dateStart.start = moment().subtract(2, 'M').startOf('month').format('YYYY-MM-DD');
                }

                if ((!this.filters.dateStart.end || !moment(this.filters.dateStart.end).isValid())) {
                    this.filters.dateStart.end = moment().endOf('month').format('YYYY-MM-DD');
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

                let bag = this.filters;

                if (parseInt(this.status) > 0) {
                    bag.status = this.status;
                }

                api.fetchAgreements(bag)
                    .then(({data}) => {
                        this.agreements = data.orders || [];
                        this.departments = data.departments || [];
                        this.production = data.production.data || [];
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
                        Event.$emit('message', {
                            type: 'success',
                            content: 'Dodano do harmonogramu produkcji.'
                        });
                        this.fetchData();
                        Event.$emit('statusUpdated');
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

            updateSort(event) {
                this.filters.meta.sort = event
            },

            getAgreementStatusName(statusId) {
                return this.statuses[statusId];
            },

            getAgreementStatusClass(statusId) {
                let className = '';
                switch (statusId) {
                    case 5:
                        className = 'badge-danger';
                        break;
                    case 10:
                        className = 'badge-primary';
                        break;
                    case 15:
                        className = 'badge-warning';
                        break;
                    case 20:
                        className = 'badge-success';
                        break;

                    default:
                        className = 'badge-primary'
                }
                return className;
            },

            getProductionStatusData(production) {

                if (production && production.data.length === 0) {
                    return {
                        className: 'badge-danger',
                        title: 'Nie zlecone'
                    };
                }
                if (production && production.data[4] && production.data[4].status === 3) {
                    return {
                        className: 'badge-success',
                        title: 'Zakończona'
                    }
                }
                if (production && production.data.length > 0) {
                    return {
                        className: 'badge-primary',
                        title: 'W trakcie'
                    }
                }

                return {
                    className: '',
                    title: ''
                };

            },

            getProductionStatusClass(production) {
                if (!production) {
                    return '';
                }
                if (production.data.length === 0) {
                    return 'badge-danger';
                }
            },

            getRouting() {
                return routing;
            },

            canUserStartProduction() {
                return this.$user.can(this.$privilages.CAN_PRODUCTION);
            },

            canUserDeleteOrder() {
                return this.$user.can(this.$privilages.CAN_ORDERS_DELETE);
            }

        },
        computed: {
            tableHeaders() {
                let headers = [
                    [
                        { name: 'ID', sortKey: 'a.orderNumber' },
                        { name: 'Data otrzymania', sortKey: 'a.createDate'},
                        { name: 'Data dostawy', sortKey: 'l.confirmedDate'},
                        { name: 'Klient', sortKey: 'c.name'},
                        { name: 'Produkt', sortKey: 'p.name' },
                        { name: 'Status zamówienia' },
                        { name: 'Status produkcji' },
                        { name: 'Akcje' },
                    ]
                ];

                return headers;
            },
        }
    }
</script>

<style scoped>
    .table thead th {
        text-align: center;
    }

    .table tbody tr {
        text-align: center;
    }
</style>