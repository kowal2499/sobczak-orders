<template>
    <div>

        <filters :model="filters"></filters>

        <table-plus :headers="tableHeaders" :loading="loading" :initialSort="'l.confirmedDate'" @sortChanged="updateSort">

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

    </div>
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

    export default {
        name: "ProductionList",

        components: { Filters, TablePlus, Tooltip, LineActions },

        props: {
            statuses: {
                type: Object,
                default: () => {}
            },
        },

        data() {
            return {
                filters: {

                    dateStart: {
                        start: '',
                        end: ''
                    },

                    dateDelivery: {
                        start: '',
                        end: ''
                    },

                    q: '',

                    deleted: false,

                    hideArchive: true,

                    meta: {
                        sort: 'l.confirmedDate:ASC',
                        page: 1,
                    }
                },

                helpers: Helpers,

                orders: [],
                departments: [],

                loading: false,

                confirmations: {
                    warehouse: {
                        show: false,
                        busy: false,
                        context: false,
                    },

                    archive: {
                        show: false,
                        busy: false,
                        context: false,
                    },

                    delete: {
                        show: false,
                        busy: false,
                        context: false,
                    }
                }
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
                        all: this.filters.showAll,
                        page: this.filters.meta.page
                    };

                    if (this.filters.dateDelivery.start && String(this.filters.dateDelivery.start).length > 0) {
                        query.dateDeliveryStart = this.filters.dateDelivery.start
                    }

                    if (this.filters.dateDelivery.end && String(this.filters.dateDelivery.end).length > 0) {
                        query.dateDeliveryEnd = this.filters.dateDelivery.end
                    }

                    query.hideArchive = this.filters.hideArchive;

                    if (this.filters.q !== '') {
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
                this.filters.dateStart.start = query.dateStart;
                this.filters.dateStart.end = query.dateEnd;
                this.filters.dateDelivery.start = query.dateDeliveryStart;
                this.filters.dateDelivery.end = query.dateDeliveryEnd;
                this.filters.q = query.q;
                this.filters.page = query.page || 1;

                if ((!this.filters.dateStart.start || !moment(this.filters.dateStart.start).isValid())) {
                    this.filters.dateStart.start = moment().subtract(2, 'M').startOf('month').format('YYYY-MM-DD');
                }

                if ((!this.filters.dateStart.end || !moment(this.filters.dateStart.end).isValid())) {
                    this.filters.dateStart.end = moment().endOf('month').format('YYYY-MM-DD');
                }

                if (moment(this.filters.dateDelivery.start).isValid() === false) {
                    this.filters.dateDelivery.start = '';
                }

                if (moment(this.filters.dateDelivery.end).isValid() === false) {
                    this.filters.dateDelivery.end = '';
                }
            },

            fetchData() {
                this.loading = true;

                let filters = {};
                for(let i of Object.keys(this.filters)) {
                    if (this.filters[i] !== '' && this.filters[i] !== null) {
                        filters[i] = this.filters[i];
                    }
                }

                api.fetchAgreements(filters)
                    .then(({data}) => {

                        if (data && data.data.orders) {

                            data.data.orders.forEach(order => {
                                order.buttonExpanded = false;
                                order.confirmRemove = false;
                                order.showCustomTasks = false;
                            });

                            this.orders = data.data.orders;
                            this.departments = data.data.departments;
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
                this.filters.meta.sort = event
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
                        { name: this.$t('ID'), sortKey: 'a.orderNumber', rowspan: 2 },
                        { name: this.$t('orders.date'), sortKey: 'l.confirmedDate', rowspan: 2 },
                        { name: this.$t('customer'), sortKey: 'c.name', rowspan: 2},
                        { name: this.$t('product'), sortKey: 'p.name', rowspan: 2 },
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
                    headers[0].splice(4, 0, { name: this.$t('orders.fctr'), sortKey: 'l.factor', rowspan: 2 });
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