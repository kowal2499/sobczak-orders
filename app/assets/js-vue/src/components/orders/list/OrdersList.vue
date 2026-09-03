<template>
    <div>
        <SectionBlockTitle block :title="$t('orders.list')" :breadcrumbs="breadcrumbs" />

        <SectionBlock class="section-gap">
            <div class="orders-toolbar">
                <a
                    v-if="userCanAddOrder()"
                    :href="newOrderLink"
                    class="btn btn-success btn-sm d-inline-flex align-items-center orders-new-btn"
                >
                    <i class="fa fa-plus" aria-hidden="true"/><span class="addNewOrder">{{ $t('newOrder') }}</span>
                </a>

                <b-pagination
                    v-if="args.meta.pages > 1"
                    class="mb-0"
                    align="right"
                    v-model="args.meta.page"
                    :total-rows="args.meta.totalCount"
                    :per-page="args.meta.pageSize"
                    first-number last-number size="sm"
                />
            </div>

            <BaseListing :listing-configuration="listing">
                <template #filters>
                    <filters :filters-collection="listing.criteriaValues" stacked />
                </template>

                <ListingTable
                    :listing="listing"
                    :items="agreementLines"
                    :loading="loading"
                    :sort="args.meta.sort"
                    :actions-label="$t('actions')"
                    sticky-header
                    @sortChanged="updateSort"
                >
                    <template #actions="{ item }">
                        <line-actions :line="item" @lineChanged="fetchData" />
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
    import Filters from './Filters';
    import api from '../../../api/neworder';
    import routing from  '../../../api/routing';
    import LineActions from '../../common/LineActions';

    import BaseListing from "@/components/base/BaseListing/index.vue";
    import ListingTable from "@/components/base/BaseListing/components/ListingTable.vue";
    import Listing from "@/components/base/BaseListing/model/Listing.js";
    import { isPreset } from "@/services/dateRangePresets";
    import {
        LISTING_ORDERS_ID,
        CRITERION_SEARCH,
        CRITERION_DATE_START,
        CRITERION_DATE_DELIVERY,
        columnsFactory as ordersListingColumnsFactory,
        criteriaFactory as ordersListingCriteriaFactory,
    } from "./configuration/ordersListing";

    const DATE_QUERY_KEYS = {
        [CRITERION_DATE_START]: { from: 'dateReceive0', to: 'dateReceive1', preset: 'dateReceivePreset' },
        [CRITERION_DATE_DELIVERY]: { from: 'dateDelivery0', to: 'dateDelivery1', preset: 'dateDeliveryPreset' },
    };

    export default {
        name: "OrdersList",

        components: { Filters, LineActions, BaseListing, ListingTable },

        props: {
            taskStatuses: {
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
                    meta: {
                        page: 0,
                        pages: 0,
                        sort: '',
                        totalCount: 0,
                        pageSize: 0
                    },
                },

                agreementLines: [],

                newOrderLink: routing.get('orders_view_new'),

                loading: false,

                listing: null,
            }
        },

        created() {
            this.listing = new Listing(
                LISTING_ORDERS_ID,
                this.$t('orders.list'),
                ordersListingColumnsFactory(),
                ordersListingCriteriaFactory()
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
                this.args.meta.sort = query.sort ? String(query.sort) : 'dateConfirmed_asc';

                // dopiero teraz przepisujemy stan do adresu - to uruchamia pierwsze pobranie
                this.syncQueryString = true;
            })
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

        computed: {

            breadcrumbs() {
                return [
                    { icon: 'home', href: '/', label: this.$t('dashboard.title') },
                    { label: this.$t('orders.list') },
                ]
            },

            criteria() {
                return this.listing ? this.listing.criteriaValues : {};
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

                const criteria = this.criteria;
                let query = {};

                for (const id of [CRITERION_DATE_START, CRITERION_DATE_DELIVERY]) {
                    const keys = DATE_QUERY_KEYS[id];
                    const value = criteria[id] || {};

                    // zakres relatywny wędruje do adresu jako token, żeby link nie zamroził dat
                    if (isPreset(value)) {
                        query[keys.preset] = value.preset;
                        continue;
                    }

                    if (value.start) {
                        query[keys.from] = value.start;
                    }
                    if (value.end) {
                        query[keys.to] = value.end;
                    }
                }

                if (criteria[CRITERION_SEARCH]) {
                    query.q = criteria[CRITERION_SEARCH];
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

                return criteria;
            },

            fetchData() {
                this.loading = true;

                const payload = this.listing.supportedCriteria.reduce((acc, criterion) => {
                    acc[criterion.apiKey] = criterion.resolveValue(this.criteria[criterion.id]);
                    return acc;
                }, {});

                payload.page = this.args.meta.page;
                payload.sort = this.args.meta.sort;

                // status jest zakresem strony (/orders/{status}), nie filtrem - nie należy do widoku
                if (parseInt(this.status) > 0) {
                    payload.status = this.status;
                }

                api.fetchAgreementsFromReadModel(payload)
                    .then(({data}) => {
                        this.agreementLines = data.data || [];
                        this.args.meta.pages = data.meta.pages || 0;
                        this.args.meta.totalCount = data.meta.totalCount || 0;
                        this.args.meta.pageSize = data.meta.pageSize || 0;
                    })
                    .catch(() => {})
                    .finally(() => {
                        this.loading = false;
                    });
            },

            updateSort(event) {
                this.args.meta.sort = event
            },

            userCanAddOrder() {
                return this.$user.can(this.$privilages.CAN_ORDERS_ADD);
            },
        },
    }
</script>

<style scoped>

.section-gap {
    margin-top: 2rem;
}

/* Przycisk i paginacja w jednym rzędzie, dosunięte do prawej. */
.orders-toolbar {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    flex-wrap: wrap;
    gap: 0.5rem 1rem;
    margin-bottom: 1rem;
}

.orders-new-btn {
    min-width: 0;
    overflow: hidden;
}

.orders-new-btn .fa-plus {
    flex: 0 0 auto; /* icon never shrinks away */
}

.orders-new-btn .addNewOrder {
    margin-left: 0.6rem; /* larger icon–text gap */
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    min-width: 0;
}

/* No room at all → icon only, with no leftover gap. */
@media screen and (max-width: 768px) {
    span.addNewOrder {
        display: none;
    }
}

</style>
