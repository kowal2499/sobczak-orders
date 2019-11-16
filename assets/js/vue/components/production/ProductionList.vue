<template>
    <collapsible-card :title="$t('orders.productionSchedule')">

        <template v-slot:filters>
            <filters :filters-collection="args.filters"></filters>
        </template>

        <pagination :current="args.meta.page" :pages="args.meta.pages" @switchPage="args.meta.page = $event"></pagination>

        <table-plus :headers="tableHeaders" :loading="loading" :initialSort="args.meta.sort" @sortChanged="updateSort">

            <tr v-for="(order, ordersKey) in orders" v-if="order.production.data.length" :key="order.line.id">
                <td>
                    {{ order.header.orderNumber || order.line.id }}
                    <div class="badge" :class="getAgreementStatusClass(order.line.status)" v-if="order.line.status !== 10">{{ $t(getAgreementStatusName(order.line.status)) }}</div>
                </td>
                <td v-text="order.line.confirmedDate"></td>
                <td v-text="__mixin_customerName(order.customer)"></td>
                <td>
                    {{ order.product.name }}
                    <tooltip v-if="order.line.description.length > 0">
                        <i slot="visible-content" class="fa fa-info-circle hasTooltip"></i>
                        <div slot="tooltip-content" class="text-left" v-html="__mixin_convertNewlinesToHtml(order.line.description)"></div>
                    </tooltip>
                </td>
                <td v-text="order.line.factor" class="text-center" v-if="userCanProduction"></td>

                <td class="production" v-for="(production, prodKey) in order.production.data" v-if="['dpt01', 'dpt02', 'dpt03', 'dpt04', 'dpt05'].indexOf(production.departmentSlug) !== -1">
                    <div class="task">
                        <select class="form-control"
                                v-model="production.status"
                                @change="updateStatus(production.id, production.status)"
                                :style="getStatusStyle(production)"
                        >
                            <option
                                    v-for="status in helpers.statusesPerTaskType(production.departmentSlug)"
                                    :value="status.value"
                                    v-text="$t(status.name)"
                                    style="background-color: white"
                                    :disabled="!userCanProduction"
                            ></option>
                        </select>
                    </div>
                </td>

                <td class="production" v-if="userCanProduction">

                    <div v-if="getCustomTasks(order.production.data).length">
                        <button href="#" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm mb-3" @click.prevent="order.showCustomTasks = !order.showCustomTasks">
                            <span v-if="order.showCustomTasks === false"><i class="fa fa-eye"></i> <span class="pl-1" >{{ $t('orders.show') }}</span></span>
                            <span v-if="order.showCustomTasks === true"><i class="fa fa-eye-slash"></i> <span class="pl-1" >{{ $t('orders.hide') }}</span></span>
                        </button>

                        <template v-if="order.showCustomTasks">
                            <div v-for="task in getCustomTasks(order.production.data)">

                                <div class="task">
                                    <label>{{ task.title }}</label>
                                    <select class="form-control"
                                            v-model="task.status"
                                            @change="updateStatus(task.id, task.status)"
                                            style="width: 120px;"
                                            :style="getStatusStyle(task)"
                                            :disabled="!userCanProduction"
                                    >
                                        <option
                                                v-for="status in helpers.statusesPerTaskType(task.departmentSlug)"
                                                :value="status.value"
                                                v-text="$t(status.name)"
                                                style="background-color: white"
                                        ></option>
                                    </select>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div v-else>
                        {{ $t('orders.na') }}
                    </div>

                </td>

                <td>
                    <line-actions :line="order" @lineChanged="fetchData()"></line-actions>
                </td>
            </tr>

        </table-plus>

        <pagination :current="args.meta.page" :pages="args.meta.pages" @switchPage="args.meta.page = $event"></pagination>

    </collapsible-card>
</template>

<script>

    import qs from 'qs';
    import moment from 'moment';
    import api from '../../api/neworder';
    import routing from '../../api/routing';
    import productionApi from '../../api/production';
    import Helpers from '../../helpers';
    import Filters from './Filters';
    import TablePlus from '../base/TablePlus';
    import Tooltip from "../base/Tooltip";
    import LineActions from "../common/LineActions";
    import CollapsibleCard from "../base/CollapsibleCard";
    import Pagination from "../base/Pagination";

    export default {
        name: "ProductionList",

        components: {Filters, TablePlus, Tooltip, LineActions, CollapsibleCard, Pagination},

        props: {
            statuses: {
                type: Object,
                default: () => {}
            },
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
                        hideArchive: true,
                        q: '',
                    },

                    meta: {
                        page: 0,
                        pages: 0,
                        sort: ''
                    },
                },

                helpers: Helpers,

                orders: [],
                departments: [],

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

            // hide active
            this.args.filters.hideArchive = query.hideArchive ? String(query.hideArchive) : '';

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

        methods: {

            fetchData() {
                this.loading = true;

                let bag = this.args.filters;
                bag.page = this.args.meta.page;
                bag.sort = this.args.meta.sort;

                api.fetchAgreements(bag)
                    .then(({data}) => {

                        if (data && data.data.orders) {

                            data.data.orders.forEach(order => {
                                order.buttonExpanded = false;
                                order.confirmRemove = false;
                                order.showCustomTasks = false;
                            });

                            this.orders = data.data.orders;
                            this.departments = data.data.departments;
                            this.args.meta.pages = data.meta.pages || 0;
                        }
                    })
                    .catch(data => {})
                    .finally(() => { this.loading = false; })
            },

            updateStatus(productionId, newStatus) {
                productionApi.updateStatus(productionId, newStatus)
                    .then(() => {
                        Event.$emit('message', {
                            type: 'success',
                            content: this.$t('statusChangeSaved')
                        });
                    })
                    .catch((error) => {
                        // TODO: gdy 403 to data zawiera htmla i nie można loopować po błędach
                        Event.$emit('message', {
                            type: 'error',
                            content: msg
                        });
                    })
                ;
            },

            getStatusStyle(production) {

                let status = this.helpers.statuses.find(item => item.value === production.status);
                if (status) {
                    return 'background-color: '.concat(status.color);
                }

                return '';
            },

            updateSort(event) {
                this.args.meta.sort = event
            },

            getCustomTasks(production) {
                return production.filter(p => { return p.departmentSlug === 'custom_task' ? p : false; })
            },

            getRouting() {
                return routing;
            },

            canArchiveOrWarehouse(order) {
                let lastProductionStage = order.production.data.find(stage => { return stage.departmentSlug === 'dpt05'; });
                return lastProductionStage.status === 3;
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
            }
        },

        computed: {
            tableHeaders() {
                let headers = [
                    [
                        { name: this.$t('ID'), sortKey: 'id', rowspan: 2 },
                        { name: this.$t('orders.date'), sortKey: 'dateConfirmed', rowspan: 2 },
                        { name: this.$t('customer'), sortKey: 'customer', rowspan: 2},
                        { name: this.$t('product'), sortKey: 'product', rowspan: 2 },
                        { name: this.$t('orders.production'), colspan: 5, classCell: 'text-center', classHeader: 'p-1 m-0'},
                    ],
                    [
                        { name: this.$t('Klejenie'), classCell: 'text-center'},
                        { name: this.$t('CNC'), classCell: 'text-center'},
                        { name: this.$t('Szlifowanie'), classCell: 'text-center'},
                        { name: this.$t('Lakierowanie'), classCell: 'text-center'},
                        { name: this.$t('Pakowanie'), classCell: 'text-center'},
                    ]

                ];

                if (this.userCanProduction) {
                    headers[0].splice(4, 0, { name: this.$t('orders.fctr'), sortKey: 'factor', rowspan: 2 });
                    headers[0].push({ name: this.$t('orders.additionalOrders'), rowspan: 2});
                }

                headers[0].push({ name: this.$t('actions'), rowspan: 2 });

                return headers;
            },

            productionSlugs() {
                return this.departments.map(d => { return d.slug; })
            },

            userCanProduction() {
                return this.$user.can(this.$privilages.CAN_PRODUCTION);
            },

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
                query.hideArchive = this.args.filters.hideArchive ? 'true' : 'false';

                query.page = this.args.meta.page;
                if (this.args.meta.sort) {
                    query.sort = this.args.meta.sort;
                }

                let qString = window.location.pathname.concat('?', qs.stringify(query));
                history.pushState(null, '', qString);

                return qString;
            },

        }
    }

</script>

<style scoped lang="scss">

    .table tbody tr {
        /*text-align: center;*/
    }

    .production {

        .task {
            width: 80px;
            label {
                font-size: 0.75rem;
                margin-bottom: 2px;
                color: #aaa;
            }
            select {
                font-size: 0.65rem;
                padding: 5px;
            }
        }

    }


</style>