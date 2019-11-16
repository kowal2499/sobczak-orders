<template>
    <div>

        <filters
            :filters-collection="args.filters"
        >
            <div class="col float-right text-right" v-if="userCanAddOrder()">
                <a :href="newOrderLink" class="btn btn-success btn-sm text-right mb-4"><i class="fa fa-plus" aria-hidden="true"></i> {{ $t('newOrder') }} </a>
            </div>
        </filters>

        <pagination :current="args.meta.page" :pages="args.meta.pages" @switchPage="args.meta.page = $event"></pagination>

        <table-plus :headers="tableHeaders" :loading="loading" :initial-sort="args.meta.sort" @sortChanged="updateSort">
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
    import ConfirmationModal from '../../base/ConfirmationModal';
    import TablePlus from '../../base/TablePlus';
    import Tooltip from '../../base/Tooltip';
    import LineActions from '../../common/LineActions';
    import Pagination from '../../base/Pagination';

    export default {
        name: "OrdersList",

        components: { Filters, Dropdown, ConfirmationModal, TablePlus, Tooltip, LineActions, Pagination },

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

                syncQueryString: false,

                args: {
                    filters: {
                        dateStart: {
                            start: '',
                            end: ''
                        },
                        q: '',
                    },

                    meta: {
                        page: 0,
                        pages: 0,
                        sort: ''
                    },
                },

                agreements: [],
                departments: [],
                // production: [],

                newOrderLink: routing.get('orders_view_new'),

                // showProductionPlan: false,
                // selectedOrder: null,
                // selectedProduction: [],

                // confirmations: {
                //     delete: {
                //         show: false,
                //         context: false,
                //         busy: false
                //     }
                // },

                loading: false,
            }
        },

        created() {

            this.syncQueryString = true;

            // parse initial query string
            let query = qs.parse(window.location.search, { ignoreQueryPrefix: true });

            // receive date
            let momentReceive0 = moment(query.dateReceive0 || null);
            let momentReceive1 = moment(query.dateReceive1 || null);

            if (momentReceive0.isValid()) {
                this.args.filters.dateStart.start = momentReceive0.format('YYYY-MM-DD');
            }

            if (momentReceive1.isValid()) {
                this.args.filters.dateStart.end = momentReceive1.format('YYYY-MM-DD');
            }

            // additional check if both dates are set and valid
            if (momentReceive0.isValid() && momentReceive1.isValid()) {
                if (momentReceive0 > momentReceive1) {
                    this.args.filters.dateStart.start = this.args.filters.dateStart.end = '';
                }
            }

            // q
            this.args.filters.q = query.q ? String(query.q) : '';

            // page
            this.args.meta.page = parseInt(query.page) || 1;

            // sort
            this.args.meta.sort = query.sort ? String(query.sort) : 'id_asc';

        },

        watch: {
            'args.filters': {
                handler() {
                    // zmiana filtrów przywraca paginację na stronę 1
                    this.args.meta.page = 1
                },
                deep: true,
            },

            queryString: {
                handler() {
                    // zmiana query string odpala pobranie danych

                    this.loading = true;

                    let bag = this.args.filters;
                    bag.page = this.args.meta.page;
                    bag.sort = this.args.meta.sort;
                    if (parseInt(this.status) > 0) {
                        bag.status = this.status;
                    }

                    api.fetchAgreements(bag)
                        .then(({data}) => {
                            this.agreements = data.data.orders || [];
                            this.agreements = data.data.orders || [];
                            this.args.meta.pages = data.meta.pages || 0;
                        })
                        .catch(data => {})
                        .finally(() => {
                            this.loading = false;
                        });
                }
            }
        },

        computed: {

            /**
             * Tworzenie queryString na podstawie zmiennych z data
             *
             * @returns {string}
             */
            queryString() {
                if (!this.syncQueryString) {
                    return;
                }
                let query = {};
                if (this.args.filters.dateStart.start) {
                    query.dateReceive0 = this.args.filters.dateStart.start;
                }
                if (this.args.filters.dateStart.end) {
                    query.dateReceive1 = this.args.filters.dateStart.end;
                }
                if (this.args.filters.q && this.args.filters.q.length > 0) {
                    query.q = this.args.filters.q;
                }
                query.page = this.args.meta.page;
                if (this.args.meta.sort) {
                    query.sort = this.args.meta.sort;
                }

                let qString = window.location.pathname.concat('?', qs.stringify(query));
                history.pushState(null, '', qString);

                return qString;
            },

            tableHeaders() {
                return [
                    [
                        { name: this.$t('id'), sortKey: 'id' },
                        { name: this.$t('receiveDate'), sortKey: 'dateReceive'},
                        { name: this.$t('deliveryDate'), sortKey: 'dateConfirmed'},
                        { name: this.$t('customer'), sortKey: 'customer'},
                        { name: this.$t('product'), sortKey: 'product' },
                        { name: this.$t('orderStatus') },
                        { name: this.$t('productionStatus') },
                        { name: this.$t('actions') },
                    ]
                ];
            },
        },


        methods: {

            updateSort(event) {
                this.args.meta.sort = event
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