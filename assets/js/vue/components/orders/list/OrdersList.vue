<template>
    <div>

        <filters
            :filters-collection="filters"
            @filtersChange="handleFiltersChange"
        >
            <div class="col float-right text-right" v-if="userCanAddOrder()">
                <a :href="newOrderLink" class="btn btn-success btn-sm text-right mb-4"><i class="fa fa-plus" aria-hidden="true"></i> {{ $t('newOrder') }} </a>
            </div>
        </filters>

        <br>

        <table-plus :headers="tableHeaders" :loading="loading" :initialSort="'l.confirmedDate'" @sortChanged="updateSort">
            <tr v-for="(agreement, key) in agreements" :key="key">
                <td>{{ agreement.header.orderNumber || agreement.line.id }}</td>
                <td>{{ agreement.header.createDate }}</td>
                <td>{{ agreement.line.confirmedDate }}</td>
                <td>{{ __mixin_customerName(agreement.customer) }}</td>
                <td>{{ agreement.product.name }}
                    <tooltip v-if="agreement.line.description.length > 0">
                        <i slot="visible-content" class="fa fa-info-circle hasTooltip"></i>
                        <div slot="tooltip-content" class="text-left" v-html="__mixin_convertNewlinesToHtml(agreement.line.description)"></div>
                    </tooltip>
                </td>
                <td><span class="badge" :class="getAgreementStatusClass(agreement.line.status)">{{ $t(getAgreementStatusName(agreement.line.status)) }}</span></td>
                <td>
                    <span class="badge badge-pill" :class="getProductionStatusData(agreement.production)['className']">
                        {{ $t(getProductionStatusData(agreement.production)['title']) }}
                    </span>
                </td>

                <td>
                    <line-actions :line="agreement" @lineChanged="fetchData()"></line-actions>
                </td>

            </tr>
        </table-plus>

    </div>
</template>

<script>
    import qs from 'qs';
    import moment from 'moment';
    import Filters from './Filters';
    import Dropdown from '../../base/Dropdown';
    import api from '../../../api/neworder';
    import routing from  '../../../api/routing';
    import ConfirmationModal from "../../base/ConfirmationModal";
    import TablePlus from '../../base/TablePlus';
    import Tooltip from "../../base/Tooltip";
    import LineActions from "../../common/LineActions";

    export default {
        name: "OrdersList",

        components: { Filters, Dropdown, ConfirmationModal, TablePlus, Tooltip, LineActions },

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
                        start: null,
                        end: null
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

            setFilters() {
                let query = qs.parse(window.location.search, { ignoreQueryPrefix: true });
                this.filters.dateStart.start = query.dateStart || '';
                this.filters.dateStart.end = query.dateEnd || '';
                this.filters.page = query.page || 1;
                this.filters.q = query.q || '';

                if (!moment(this.filters.dateStart.start).isValid()) {
                    this.filters.dateStart.start = '';
                }

                if (!moment(this.filters.dateStart.end).isValid()) {
                    this.filters.dateStart.end = '';
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

            userCanAddOrder() {
                return this.$user.can(this.$privilages.CAN_ORDERS_ADD);
            }

        },
        computed: {
            tableHeaders() {
                let headers = [
                    [
                        { name: this.$t('id'), sortKey: 'a.orderNumber' },
                        { name: this.$t('receiveDate'), sortKey: 'a.createDate'},
                        { name: this.$t('deliveryDate'), sortKey: 'l.confirmedDate'},
                        { name: this.$t('customer'), sortKey: 'c.name'},
                        { name: this.$t('product'), sortKey: 'p.name' },
                        { name: this.$t('orderStatus') },
                        { name: this.$t('productionStatus') },
                        { name: this.$t('actions') },
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