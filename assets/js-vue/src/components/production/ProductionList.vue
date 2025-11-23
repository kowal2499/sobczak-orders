<template>
    <collapsible-card :title="$t('orders.productionSchedule')">
        <template v-slot:filters>
            <filters :filters-collection="args.filters">
<!--                <b-dropdown size="sm" variant="outline-primary">-->
<!--                    <template #button-content>-->
<!--                        <i class="fa fa-cog" aria-hidden="true" />-->
<!--                    </template>-->
<!--                    <b-dropdown-item-button @click.prevent="onFiltersClear">-->
<!--                        Wyczyść ustawienia filtrów-->
<!--                    </b-dropdown-item-button>-->
<!--                </b-dropdown>-->
            </filters>
        </template>

        <b-pagination
            v-if="args.meta.pages > 1"
            align="right"
            v-model="args.meta.page"
            :total-rows="args.meta.totalCount"
            :per-page="args.meta.pageSize"
            first-number last-number size="sm"
        />

        <table-plus :headers="tableHeaders" :loading="loading" :initialSort="args.meta.sort" @sortChanged="updateSort">
            <template v-for="order in orders">
                <production-row
                    v-if="order.productions.length > 0"
                    :order="order"
                    :statuses="statuses"
                    :key="order.id"
                    :disabled="busyOrders.includes(order.id)"
                    @statusUpdated="updateStatus($event, order.id)"
                    @lineChanged="fetchData"
                    @expandToggle="prodExpanded === $event ? prodExpanded = null : prodExpanded = $event"
                />

                <production-row-details
                    v-if="order.id === prodExpanded"
                    :order="order"
                    :statuses="statuses"
                    :key="'details' + order.id"
                    :disabled="busyOrders.includes(order.id)"
                    :columns-count="tableHeaders.length"
                    @statusUpdated="updateStatus($event, order.id)"
                />
            </template>
        </table-plus>

        <b-pagination
                v-if="args.meta.pages > 1"
                align="right"
                v-model="args.meta.page"
                :total-rows="args.meta.totalCount"
                :per-page="args.meta.pageSize"
                first-number last-number size="sm"
        />
    </collapsible-card>
</template>

<script>
    import qs from 'qs';
    import moment from 'moment';
    import api from '../../api/neworder';
    import routing from '../../api/routing';
    import productionApi from '../../api/production';
    import Helpers, {DEPARTMENTS} from '../../helpers';
    import Filters from './Filters';
    import TablePlus from '../base/TablePlus';
    import CollapsibleCard from "../base/CollapsibleCard";
    import ProductionRow from "./ProductionRow";
    import ProductionRowDetails from "./ProductionRowDetails";
    import { resolveDefaultOrder } from "../../modules/agreementLineList/services/DefaultSortDateResolver"

    export default {
        name: "ProductionList",

        components: {Filters, TablePlus, CollapsibleCard, ProductionRow, ProductionRowDetails},

        props: {
            statuses: {
                type: Object,
                default: () => {}
            },
            departments: {
                type: Array,
                default: () => []
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
                        hideArchive: true,
                        hasProduction: true,
                        q: '',
                    },
                    meta: {
                        page: 0,
                        pages: 0,
                        totalCount: 0,
                        pageSize: 0,
                        sort: ''
                    },
                },
                helpers: Helpers,
                orders: [],
                prodExpanded: null,
                loading: false,
                busyOrders: [],
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
            if (query.hideArchive === 'true' || query.hideArchive === undefined) {
                this.args.filters.hideArchive = true;
            } else {
                this.args.filters.hideArchive = false;
            }
            // this.args.filters.hideArchive = query.hideArchive === 'true' ? false : '';

            // page
            this.args.meta.page = parseInt(query.page) || 1;

            // sort
            this.args.meta.sort = query.sort ? String(query.sort) : resolveDefaultOrder(this.$user);
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
                        if (data && data.data) {

                            data.data.forEach(order => {
                                order.buttonExpanded = false;
                                order.confirmRemove = false;
                                order.showCustomTasks = false;
                            });

                            this.orders = data.data;

                        } else {
                            this.orders = [];
                        }
                        this.args.meta.pages = data.meta.pages || 0;
												this.args.meta.totalCount = data.meta.totalCount || 0;
												this.args.meta.pageSize = data.meta.pageSize || 0;
                    })
                    .catch(() => {})
                    .finally(() => this.loading = false)
            },

            updateStatus(data, agreementLineId) {
                const taskId = data.id;
                const newStatus = data.status;
                const lineId = agreementLineId
                this.busyOrders.push(agreementLineId);

                productionApi.updateStatus(taskId, newStatus)
                    .then(() => this.fetchSingleLine(lineId))
                    .then(() => {
                        EventBus.$emit('message', {
                            type: 'success',
                            content: this.$t('statusChangeSaved')
                        });
                    })
                    .catch((error) => {
                        let msg = '';
                        if (error.response && error.response.status) {
                            switch (error.response.status) {
                                case 403:
                                    msg = this.$t('forbidden');
                                    break;
                                default:
                                    msg = this.$t('error');
                            }
                        }
                        EventBus.$emit('message', {
                            type: 'error',
                            content: msg
                        });
                    })
                    .finally(() => {
                        this.busyOrders = this.busyOrders.filter(order => order !== lineId)
                    });
            },

            fetchSingleLine(agreementLineId) {
                productionApi.fetchSingleLine(agreementLineId)
                .then(({data}) => {
                    this.orders = this.orders.map(order => {
                        return order.id === data.id ? data : order
                    })
                })
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

            onFiltersClear() {
                this.args.filters.dateStart.start = null
                this.args.filters.dateStart.end = null
                this.args.filters.dateDelivery.start = null
                this.args.filters.dateDelivery.end = null
                this.args.filters.q = ''
                this.args.filters.hideArchive = true
                this.args.meta.sort = ''
                this.args.meta.page = 1
            }
        },

        computed: {
            productionDepartmentHeaders() {
                return this.departments.map((department, index) => {
                    const dep = DEPARTMENTS.find(d => d.slug === department.slug);
                    if (!dep) {
                        return null
                    }
                    const backgroundColorClass = (index % 2) ? 'background-color-primary-light-90' : 'background-color-primary-light-80';
                    const thClass = 'text-center'.concat(' ', backgroundColorClass)
                    return (this.$user.can(dep.grant) && {
                        thClass,
                        items: [
                            {
                                name: this.$t(`_${department.slug}`),
                            },
                            {
                                name: this.$t('agreement_line_list.startProductionForm.startDate'),
                                sortKey: `${department.slug}DateStart`,
                                wrapperClass: 'text-nowrap',
                            },
                            {
                                name: this.$t('agreement_line_list.startProductionForm.endDate'),
                                sortKey: `${department.slug}DateEnd`,
                                wrapperClass: 'text-nowrap',
                            }
                        ]}) || null
                }).filter(Boolean)
            },

            tableHeaders() {
                const headers = [
                    { name: this.$t('actions')},
                    { name: this.$t('ID'), sortKey: 'id' },
                    this.$user.can('production.show.production_date') && { name: this.$t('orders.date'), sortKey: 'dateConfirmed' },
                    { name: this.$t('orders.issuedBy'), sortKey: 'user' },
                    { name: this.$t('customer'), sortKey: 'customer' },
                    { name: this.$t('product'), sortKey: 'product' },
                    this.userCanProduction && { name: this.$t('orders.fctr'), sortKey: 'factor'},
                ].filter(Boolean).map(i => ({ items: [i], thClass: null }))

                headers.push(...this.productionDepartmentHeaders)
                return headers
            },

            productionSlugs() {
                return this.departments.map(d => { return d.slug; })
            },

            userCanProduction() {
                return this.$user.can(this.$privilages.CAN_PRODUCTION);
            },

            userCanSeeProductionDate() {
                return this.$user.can('production.show.production_date');
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


</style>