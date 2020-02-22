<template>
    <collapsible-card :title="$t('orders.list')">

        <template v-slot:header>
            <div v-if="userCanAddOrder()">
                <a :href="newOrderLink" class="btn btn-success btn-sm text-right m-1"><i class="fa fa-plus" aria-hidden="true"/><span class="addNewOrder">{{ $t('newOrder') }}</span></a>
            </div>
        </template>

        <template v-slot:filters>
            <filters :filters-collection="args.filters"/>
        </template>

        <pagination :current="args.meta.page" :pages="args.meta.pages" @switchPage="args.meta.page = $event"/>

        <table-plus :headers="tableHeaders" :loading="loading" :initial-sort="args.meta.sort" @sortChanged="updateSort">
            <tr v-for="(line, key) in agreementLines" :key="key">
                <td>{{ line.Agreement.orderNumber || line.Agreement.id }}</td>
                <td>{{ line.Agreement.createDate | formatDate('YYYY-MM-DD') }}</td>
                <td>{{ line.confirmedDate | formatDate('YYYY-MM-DD') }}</td>
                <td>{{ __mixin_customerName(line.Agreement.Customer) }}</td>
                <td>{{ line.Product.name }}
                    <tooltip v-if="line.description && line.description.length > 0">
                        <i slot="visible-content" class="fa fa-info-circle hasTooltip"/>
                        <div slot="tooltip-content" class="text-left" v-html="__mixin_convertNewlinesToHtml(line.description)"></div>
                    </tooltip>
                    <span v-if="line.Agreement.attachments && line.Agreement.attachments.length > 0"><i class="fa fa-paperclip sb-color"/></span>
                </td>
                <td><span class="badge" :class="getAgreementStatusClass(line.status)">{{ $t(getAgreementStatusName(line.status)) }}</span></td>
                <td>
                    <span class="badge badge-pill" :class="getProductionStatusData(line.productions)['className']">
                        {{ $t(getProductionStatusData(line.productions)['title']) }}
                    </span>
                </td>

                <td>
                    <line-actions :line="line" @lineChanged="fetchData()"/>
                </td>

            </tr>
        </table-plus>

        <pagination :current="args.meta.page" :pages="args.meta.pages" @switchPage="args.meta.page = $event"/>

    </collapsible-card>
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
    import CollapsibleCard from '../../base/CollapsibleCard';

    export default {
        name: "OrdersList",

        components: { Filters, Dropdown, ConfirmationModal, TablePlus, Tooltip, LineActions, Pagination, CollapsibleCard },

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
                            start: null,
                            end: null
                        },
                        dateDelivery: {
                            start: null,
                            end: null
                        },
                        q: '',
                    },

                    meta: {
                        page: 0,
                        pages: 0,
                        sort: ''
                    },
                },

                agreementLines: [],
                // departments: [],
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

            for (let i of [
                    {
                        moment0: moment(query.dateReceive0 || null),
                        moment1: moment(query.dateReceive1 || null),
                        store0: 'args.filters.dateStart.start',
                        store1: 'args.filters.dateStart.end',
                    },
                    {
                        moment0: moment(query.dateDelivery0 || null),
                        moment1: moment(query.dateDelivery1 || null),
                        store0: 'args.filters.dateDelivery.start',
                        store1: 'args.filters.dateDelivery.end',
                    },
            ]) {

                // both dates need to be set and valid
                if (i.moment0.isValid() && i.moment1.isValid() && i.moment0 <= i.moment1) {
                    _.set(this, i.store0, i.moment0.format('YYYY-MM-DD'));
                    _.set(this, i.store1, i.moment1.format('YYYY-MM-DD'));
                }
            }

            // q
            this.args.filters.q = query.q ? String(query.q) : '';

            // page
            this.args.meta.page = parseInt(query.page) || 1;

            // sort
            this.args.meta.sort = query.sort ? String(query.sort) : 'dateConfirmed_asc';

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
                    this.fetchData();
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
                if (this.args.filters.dateDelivery.start) {
                    query.dateDelivery0 = this.args.filters.dateDelivery.start;
                }
                if (this.args.filters.dateDelivery.end) {
                    query.dateDelivery1 = this.args.filters.dateDelivery.end;
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

            fetchData() {
                this.loading = true;

                let bag = this.args.filters;
                bag.page = this.args.meta.page;
                bag.sort = this.args.meta.sort;
                if (parseInt(this.status) > 0) {
                    bag.status = this.status;
                }

                api.fetchAgreements(bag)
                    .then(({data}) => {
                        this.agreementLines = data.data || [];
                        this.args.meta.pages = data.meta.pages || 0;
                    })
                    .catch(data => {})
                    .finally(() => {
                        this.loading = false;
                    });
            },

            updateSort(event) {
                this.args.meta.sort = event
            },

            getAgreementStatusName(statusId) {
                return this.statuses[parseInt(statusId)];
            },

            getAgreementStatusClass(statusId) {
                let className = '';
                switch (parseInt(statusId)) {
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

                if (production && production.length === 0) {
                    return {
                        className: 'badge-danger',
                        title: 'Nie zlecone'
                    };
                }
                if (production && production[4] && parseInt(production[4].status) === 3) {
                    return {
                        className: 'badge-success',
                        title: 'Zakończona'
                    }
                }
                if (production && production.length > 0) {
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

@media screen and (max-width: 768px) {
    span.addNewOrder {
        display: none;
    }
}

</style>