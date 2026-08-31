<template>
    <div>
        <SectionBlockTitle block :title="$t('orders.productionSchedule')" :breadcrumbs="breadcrumbs">
            <template #filters>
                <filters :filters-collection="args.filters" />
            </template>
        </SectionBlockTitle>

        <SectionBlock class="section-gap">
            <b-pagination
                v-if="args.meta.pages > 1"
                align="right"
                v-model="args.meta.page"
                :total-rows="args.meta.totalCount"
                :per-page="args.meta.pageSize"
                first-number last-number size="sm"
            />

            <BaseListing :listing-configuration="listing">
                <ListingTable
                    :listing="listing"
                    :items="rows"
                    :loading="loading"
                    :sort="args.meta.sort"
                    :cell-props="{ taskStatuses }"
                    :row-disabled="isRowDisabled"
                    :actions-label="$t('actions')"
                    row-key="agreementLineId"
                    sticky-header
                    @sortChanged="updateSort"
                    @statusUpdated="updateStatus"
                    @taskStatusUpdated="updateTaskStatus"
                    @lineChanged="fetchData"
                >
                    <template #actions="{ item, disabled }">
                        <line-actions :line="item" :disabled="disabled" @lineChanged="fetchData" />
                    </template>
                </ListingTable>
            </BaseListing>

            <b-pagination
                v-if="args.meta.pages > 1"
                align="right"
                v-model="args.meta.page"
                :total-rows="args.meta.totalCount"
                :per-page="args.meta.pageSize"
                first-number last-number size="sm"
            />
        </SectionBlock>
    </div>
</template>

<script>
import qs from 'qs';
import moment from 'moment';
import routing from '../../../api/routing';
import productionApi from '../../../api/production';
import Helpers from '../../../helpers';
import Filters from '../../../components/production/Filters';
import LineActions from "../components/LineActions2";
import { resolveDefaultOrder } from "../../agreementLineList/services/DefaultSortDateResolver"
import { rmSearch, rmFetchSingle } from '../repository/readModelRepository'
import { updateTaskStatus } from '../../task/repository/taskRepository'

import BaseListing from "@/components/base/BaseListing/index.vue";
import ListingTable from "@/components/base/BaseListing/components/ListingTable.vue";
import Listing from "@/components/base/BaseListing/model/Listing.js";
import { LISTING_PRODUCTION_ID, columnsFactory as productionListingColumnsFactory } from "../configuration/productionListing";

export default {
    name: "AgreementLineListRM",

    components: { Filters, LineActions, BaseListing, ListingTable },

    props: {
        taskStatuses: {
            type: Object,
            default: () => {}
        }
    },

    created() {
        // listing initialization
        this.listing = new Listing(
            LISTING_PRODUCTION_ID,
            this.$t('orders.productionSchedule'),
            productionListingColumnsFactory(this.$user)
        )
        this.listing.onPersistError(() => this.$flash.danger(this.$t('listing.saveError')))
        this.listing.fetchViews()

        //
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

    computed: {
        breadcrumbs() {
            return [
                { icon: 'home', href: '/', label: this.$t('dashboard.title') },
                { label: this.$t('orders.productionSchedule') },
            ]
        },

        rows() {
            return this.orders.filter(order => (order.productions || []).length > 0)
        },

        userCanProduction() {
            return this.$user.can(this.$privilages.CAN_PRODUCTION);
        },

        userCanReadTasks() {
            return this.$user.can('task.orphans:read');
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

            let payload = this.args.filters;
            payload.page = this.args.meta.page;
            payload.sort = this.args.meta.sort;

            rmSearch(payload)
                .then(({data}) => {
                    if (data && data.data) {
                        data.data.forEach(order => {
                            order.meta = {
                                buttonExpanded: false,
                                confirmRemove: false,
                                showCustomTasks: false,
                            }
                        });
                        this.orders = data.data;
                    } else {
                        this.orders = [];
                    }
                    this.args.meta.pages = data.meta.pages || 0;
                    this.args.meta.totalCount = data.meta.totalCount || 0;
                    this.args.meta.pageSize = data.meta.pageSize || 0;
                })
                .finally(() => this.loading = false)
        },

        updateTaskStatus({ id, status }, agreementLineId) {
            this.busyOrders.push(agreementLineId);
            return updateTaskStatus(id, status)
                .then(() => this.fetchSingleLine(agreementLineId))
                .then(() => this.$flash.success(this.$t('statusChangeSaved')))
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
                    this.$flash.danger(msg)
                })
                .finally(() => {
                    this.busyOrders = this.busyOrders.filter(order => order !== agreementLineId)
                });
        },

        updateStatus(data, agreementLineId) {
            const taskId = data.id;
            const newStatus = data.status;
            const lineId = agreementLineId
            this.busyOrders.push(agreementLineId);

            productionApi.updateStatus(taskId, newStatus)
                .then(() => this.fetchSingleLine(lineId))
                .then(() => this.$flash.success(this.$t('statusChangeSaved')))
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
                    this.$flash.danger(msg)
                })
                .finally(() => {
                    this.busyOrders = this.busyOrders.filter(order => order !== lineId)
                });
        },

        fetchSingleLine(agreementLineId) {
            return rmFetchSingle(agreementLineId)
            .then(({data}) => {
                this.orders = this.orders.map(order => {
                    return order.agreementLineId === data.agreementLineId ? data : order
                })
            })
        },

        getStatusStyle(production) {
            let status = this.helpers.taskStatuses.find(item => item.value === production.status);
            if (status) {
                return 'background-color: '.concat(status.color);
            }

            return '';
        },

        updateSort(event) {
            this.args.meta.sort = event
        },

        isRowDisabled(order) {
            return this.busyOrders.includes(order.agreementLineId)
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
            loading: false,
            busyOrders: [],
            listing: null
        }
    },
}

</script>

<style scoped lang="scss">

.section-gap {
    margin-top: 2rem;
}

</style>