<template>
    <div>
        <SectionBlockTitle block :title="$t('orders.productionSchedule')" :breadcrumbs="breadcrumbs" />

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
                <template #filters>
                    <filters :filters-collection="listing.criteriaValues" stacked />
                </template>

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
import {
    LISTING_PRODUCTION_ID,
    CRITERION_SEARCH,
    CRITERION_DATE_START,
    CRITERION_DATE_DELIVERY,
    CRITERION_HIDE_ARCHIVE,
    CRITERION_NOT_STARTED,
    CRITERION_START_DELAYED,
    columnsFactory as productionListingColumnsFactory,
    criteriaFactory as productionListingCriteriaFactory,
} from "../configuration/productionListing";
import { isPreset } from "@/services/dateRangePresets";

const DATE_QUERY_KEYS = {
    [CRITERION_DATE_START]: { from: 'dateReceive0', to: 'dateReceive1', preset: 'dateReceivePreset' },
    [CRITERION_DATE_DELIVERY]: { from: 'dateDelivery0', to: 'dateDelivery1', preset: 'dateDeliveryPreset' },
};

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
        this.listing = new Listing(
            LISTING_PRODUCTION_ID,
            this.$t('orders.productionSchedule'),
            productionListingColumnsFactory(this.$user),
            productionListingCriteriaFactory(this.$user)
        )
        this.listing.onPersistError(() => this.$flash.danger(this.$t('listing.saveError')))

        const query = qs.parse(window.location.search, { ignoreQueryPrefix: true });
        const queryCriteria = this.parseQueryCriteria(query);

        this.listing.fetchViews().then(() => {
            // Filtry z adresu mają pierwszeństwo przed zapisanymi w widoku, żeby wysłany
            // link otwierał to, co widział nadawca. Rozjazd pokaże się jako niezapisane zmiany.
            if (Object.keys(queryCriteria).length) {
                this.listing.setCriteriaValues({ ...this.listing.criteriaValues, ...queryCriteria });
            }

            this.args.meta.page = parseInt(query.page) || 1;
            this.args.meta.sort = query.sort ? String(query.sort) : resolveDefaultOrder(this.$user);

            // dopiero teraz przepisujemy stan do adresu - to uruchamia pierwsze pobranie
            this.syncQueryString = true;
        })
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
        criteria() {
            return this.listing ? this.listing.criteriaValues : {};
        },

        queryString() {
            if (!this.syncQueryString) {
                return;
            }

            const criteria = this.criteria;
            let query = {};

            for (const range of [
                { value: criteria[CRITERION_DATE_START], keys: DATE_QUERY_KEYS[CRITERION_DATE_START] },
                { value: criteria[CRITERION_DATE_DELIVERY], keys: DATE_QUERY_KEYS[CRITERION_DATE_DELIVERY] },
            ]) {
                const value = range.value || {};

                // zakres relatywny wędruje do adresu jako token, żeby link nie zamroził dat
                if (isPreset(value)) {
                    query[range.keys.preset] = value.preset;
                    continue;
                }

                if (value.start) {
                    query[range.keys.from] = value.start;
                }
                if (value.end) {
                    query[range.keys.to] = value.end;
                }
            }

            if (criteria[CRITERION_SEARCH]) {
                query.q = criteria[CRITERION_SEARCH];
            }
            query.hideArchive = criteria[CRITERION_HIDE_ARCHIVE] ? 'true' : 'false';
            if (criteria[CRITERION_NOT_STARTED]) {
                query.notStarted = 'true';
            }
            if (criteria[CRITERION_START_DELAYED]) {
                query.startDelayed = 'true';
            }

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
        criteria: {
            handler() {
                // zmiana filtrów przywraca paginację na stronę 1
                if (this.syncQueryString) {
                    this.args.meta.page = 1
                }
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
        /**
         * Filtry z adresu. Zwraca tylko te, które faktycznie w nim były.
         *
         * @param {Object} query
         * @returns {Object}
         */
        parseQueryCriteria(query) {
            const criteria = {};

            for (const id of [CRITERION_DATE_START, CRITERION_DATE_DELIVERY]) {
                const keys = DATE_QUERY_KEYS[id];

                if (isPreset({ preset: query[keys.preset] })) {
                    criteria[id] = { preset: query[keys.preset] };
                    continue;
                }

                const from = moment(query[keys.from] || null);
                const to = moment(query[keys.to] || null);

                // obie daty muszą być poprawne i w kolejności
                if (from.isValid() && to.isValid() && from <= to) {
                    criteria[id] = { start: from.format('YYYY-MM-DD'), end: to.format('YYYY-MM-DD') };
                }
            }

            if (query.q !== undefined) {
                criteria[CRITERION_SEARCH] = String(query.q);
            }

            if (query.hideArchive !== undefined) {
                criteria[CRITERION_HIDE_ARCHIVE] = query.hideArchive === 'true';
            }

            if (query.notStarted !== undefined) {
                criteria[CRITERION_NOT_STARTED] = query.notStarted === 'true';
            }

            if (query.startDelayed !== undefined) {
                criteria[CRITERION_START_DELAYED] = query.startDelayed === 'true';
            }

            return criteria;
        },

        fetchData() {
            this.loading = true;

            const payload = this.listing.supportedCriteria.reduce((acc, criterion) => {
                acc[criterion.apiKey] = criterion.resolveValue(this.criteria[criterion.id]);
                return acc;
            }, { hasProduction: true });

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
        }
    },

    data() {
        return {
            syncQueryString: false,
            args: {
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