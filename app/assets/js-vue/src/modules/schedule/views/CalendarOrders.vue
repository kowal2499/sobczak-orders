<script>
import ResourceCalendar from "@/modules/schedule/components/ResourceCalendar/ResourceCalendar.vue";
import { openOrderPanel } from "@/modules/agreement/orderPanel";
import VueSelect from 'vue-select'
import moment from 'moment'
import { fetchOrderResources, updateProductionDates, fetchHolidays } from "@/modules/schedule/repository/scheduleRepository";
import { describeSelection } from "@/modules/schedule/filters/describeSelection";
import FiltersDropdown from "@/components/base/FiltersDropdown.vue";
import BaseListing from "@/components/base/BaseListing/index.vue";
import Listing from "@/components/base/BaseListing/model/Listing.js";
import Criterion, { TYPE_LIST } from "@/components/base/BaseListing/model/Criterion.js";

const LISTING_ID = 'calendar-orders'
// AgreementLine statuses (DELETED is filtered out server-side)
const ORDER_STATUSES = [5, 10, 15, 20]
const EDITABLE_STATUSES = ['awaits', 'started', 'in_progress']
// Compact row height so a single-bar row hugs the bar (bar is 24px tall)
const ROW_HEIGHT = 32

export default {
    name: "CalendarOrders",

    components: {
        ResourceCalendar,
        VueSelect,
        FiltersDropdown,
        BaseListing,
    },

    data() {
        return {
            currentMonth: moment().format('YYYY-MM'),
            listing: null,
            loading: false,
            orders: [],
            holidays: [],
            expanded: {},
            // order numbers seen this session, so a chip still names an order outside the month
            knownOrders: {},
        }
    },

    computed: {
        breadcrumbs() {
            return [
                { icon: 'home', href: '/', label: this.$t('schedule.breadcrumb.home') },
                { label: this.$t('schedule.orders.breadcrumb') },
            ]
        },

        allLabel() {
            return this.$t('schedule.all')
        },

        statusOptions() {
            return ORDER_STATUSES.map(s => ({
                value: s,
                label: this.$t(`schedule.orders.status.${s}`),
            }))
        },

        agreementLineOptions() {
            return this.orders
                .map(o => ({
                    value: o.id,
                    label: [o.orderNumber, o.customerName, o.productName]
                        .filter(Boolean)
                        .join(' - ') || `#${o.id}`,
                    orderNumber: o.orderNumber || '',
                }))
                .sort((a, b) => String(a.orderNumber).localeCompare(String(b.orderNumber)))
        },

        knownOrderOptions() {
            return Object.keys(this.knownOrders).map(id => ({ value: Number(id), label: this.knownOrders[id] }))
        },

        filters() {
            const values = (this.listing && this.listing.criteriaValues) || {}
            return {
                statuses: values.statuses || [],
                agreementLineIds: values.agreementLineIds || [],
            }
        },

        activeFilterCount() {
            return this.listing ? this.listing.activeCriteria.length : 0
        },

        filteredOrders() {
            const { statuses, agreementLineIds } = this.filters
            return this.orders.filter(o => {
                if (statuses.length && !statuses.includes(o.status)) return false
                if (agreementLineIds.length && !agreementLineIds.includes(o.id)) return false
                return true
            })
        },

        resources() {
            const rows = []
            for (const order of this.filteredOrders) {
                rows.push({ id: 'o' + order.id, type: 'order', order, rowHeight: ROW_HEIGHT })
                if (this.isExpanded(order.id)) {
                    for (const p of order.productions) {
                        rows.push({
                            id: 'o' + order.id + '-' + p.departmentSlug,
                            type: 'department',
                            name: p.departmentName,
                            rowHeight: ROW_HEIGHT,
                        })
                    }
                }
            }
            return rows
        },

        events() {
            const events = []
            for (const order of this.filteredOrders) {
                const orderName = [order.orderNumber, order.customerName]
                    .filter(Boolean)
                    .join(' · ')
                events.push({
                    id: 'order-' + order.id,
                    resourceId: 'o' + order.id,
                    agreementLineId: order.id,
                    orderName,
                    eventType: 'orderRange',
                    orderStatus: 'order_range',
                    dateStart: order.dateStart,
                    dateEnd: order.dateEnd,
                    meta: {
                        agreementLineId: order.id,
                        orderNumber: order.orderNumber,
                        customerName: order.customerName,
                        productName: order.productName,
                    },
                })

                if (!this.isExpanded(order.id)) continue

                for (const p of order.productions) {
                    events.push({
                        id: 'prod-' + p.id,
                        resourceId: 'o' + order.id + '-' + p.departmentSlug,
                        agreementLineId: order.id,
                        orderName: order.orderNumber,
                        orderStatus: p.status,
                        eventType: 'order',
                        dateStart: p.dateStart,
                        dateEnd: p.dateEnd,
                        meta: {
                            productionId: p.id,
                            agreementLineId: order.id,
                            departmentSlug: p.departmentSlug,
                            orderNumber: order.orderNumber,
                            customerName: order.customerName,
                            productName: order.productName,
                        },
                    })
                }
            }
            return events
        },
    },

    created() {
        this.listing = new Listing(LISTING_ID, this.$t('schedule.orders.title'), [], this.buildCriteria())
        this.listing.onPersistError(() => this.$flash.danger(this.$t('listing.saveError')))
        this.listing.fetchViews()
        this.loadMonth(this.currentMonth)
    },

    mounted() {
        window.EventBus.$on('agreementLineSaved', this.onPanelSaved)
    },

    beforeDestroy() {
        window.EventBus.$off('agreementLineSaved', this.onPanelSaved)
    },

    methods: {
        buildCriteria() {
            const status = this.$t('schedule.filters.status')
            const order = this.$t('schedule.filters.agreementLine')

            return [
                new Criterion({
                    id: 'statuses',
                    label: status,
                    type: TYPE_LIST,
                    defaultValue: [],
                    formatter: ids => describeSelection(status, ids, this.statusOptions),
                }),
                new Criterion({
                    id: 'agreementLineIds',
                    label: order,
                    type: TYPE_LIST,
                    defaultValue: [],
                    formatter: ids => describeSelection(order, ids, this.knownOrderOptions),
                }),
            ]
        },

        setFilter(id, value) {
            this.listing.setCriterionValue(id, value)
        },

        rememberOrders(orders) {
            for (const o of orders) {
                if (o.orderNumber && !this.knownOrders[o.id]) {
                    this.$set(this.knownOrders, o.id, o.orderNumber)
                }
            }
        },

        labelSlot(id) {
            return 'resource-label-' + id
        },

        isExpanded(orderId) {
            return !!this.expanded[orderId]
        },

        toggle(orderId) {
            this.expanded = { ...this.expanded, [orderId]: !this.expanded[orderId] }
        },

        hasDetails(order) {
            return order.productions && order.productions.length > 0
        },

        rangeFor(month) {
            const m = moment(month, 'YYYY-MM')
            return {
                start: m.clone().startOf('month').format('YYYY-MM-DD'),
                end: m.clone().endOf('month').format('YYYY-MM-DD'),
            }
        },

        loadMonth(month) {
            if (false === this.$user.can('reports.calendar_orders')) {
                return Promise.resolve()
            }
            const { start, end } = this.rangeFor(month)
            this.loading = true
            this.loadHolidays(start, end)
            return fetchOrderResources(start, end)
                .then(({ data }) => {
                    this.orders = data.orders || []
                    this.rememberOrders(this.orders)
                })
                .catch(() => {
                    window.EventBus.$emit('message', {
                        type: 'danger',
                        content: this.$t('schedule.loadError'),
                    })
                })
                .finally(() => {
                    this.loading = false
                })
        },

        loadHolidays(start, end) {
            return fetchHolidays(start, end)
                .then(({ data }) => {
                    this.holidays = (data || [])
                        .filter(h => h.date)
                        .map(h => ({ date: h.date, description: h.description || '' }))
                })
                .catch(() => {
                    this.holidays = []
                })
        },

        onMonthChange(month) {
            this.currentMonth = month
            this.loadMonth(month)
        },

        canEditEvent(event) {
            return event.eventType === 'order'
                && this.$user.can('production.panel')
                && EDITABLE_STATUSES.includes(event.orderStatus)
        },

        onEventClick(event) {
            openOrderPanel(this.$router, event.agreementLineId, (event.meta && event.meta.departmentSlug) || null)
        },

        onEventMoved({ previous, updated }) {
            if (
                previous.dateStart === updated.dateStart
                && previous.dateEnd === updated.dateEnd
            ) {
                return
            }
            this.saveDates(updated)
        },

        onEventResized({ updated }) {
            this.saveDates(updated)
        },

        saveDates(event) {
            const productionId = event.meta && event.meta.productionId
            if (!productionId) {
                return
            }
            return updateProductionDates(productionId, {
                dateStart: event.dateStart,
                dateEnd: event.dateEnd,
            })
                .then(() => {
                    this.applyProductionDates(productionId, event.dateStart, event.dateEnd)
                    window.EventBus.$emit('message', {
                        type: 'success',
                        content: this.$t('schedule.saved'),
                    })
                })
                .catch(() => {
                    window.EventBus.$emit('message', {
                        type: 'danger',
                        content: this.$t('schedule.saveError'),
                    })
                    this.loadMonth(this.currentMonth)
                })
        },

        applyProductionDates(productionId, dateStart, dateEnd) {
            for (const order of this.orders) {
                const target = (order.productions || []).find(p => p.id === productionId)
                if (target) {
                    target.dateStart = dateStart
                    target.dateEnd = dateEnd
                    return
                }
            }
        },

        onPanelSaved() {
            this.loadMonth(this.currentMonth)
        },
    },
}
</script>

<template>
    <div class="page-fill">
        <SectionBlockTitle block :title="$t('schedule.orders.title')" :breadcrumbs="breadcrumbs">
            <template #actions>
                <b-nav>
                    <FiltersDropdown :count="activeFilterCount" @clear="listing.clearCriteria()">
                        <div class="calendar-filter">
                            <label class="form-label small mb-1">{{ $t('schedule.filters.status') }}</label>
                            <vue-select
                                :value="filters.statuses"
                                @input="setFilter('statuses', $event)"
                                :options="statusOptions"
                                :reduce="o => o.value"
                                :placeholder="allLabel"
                                multiple
                            />
                        </div>
                        <div class="calendar-filter">
                            <label class="form-label small mb-1">{{ $t('schedule.filters.agreementLine') }}</label>
                            <vue-select
                                :value="filters.agreementLineIds"
                                @input="setFilter('agreementLineIds', $event)"
                                :options="agreementLineOptions"
                                :reduce="o => o.value"
                                :placeholder="allLabel"
                                multiple
                            />
                        </div>
                    </FiltersDropdown>
                </b-nav>
            </template>
        </SectionBlockTitle>

        <SectionBlock class="section-gap page-fill__body">
            <BaseListing class="calendar-listing" :listing-configuration="listing">
            <ResourceCalendar
                :resources="resources"
                :events="events"
                :holidays="holidays"
                :value="currentMonth"
                :interactive="true"
                :allow-create="false"
                :can-edit-event="canEditEvent"
                @month-change="onMonthChange"
                @event-click="onEventClick"
                @event-moved="onEventMoved"
                @event-resized="onEventResized"
            >
                <!-- Existing per-row label slot, targeted via dynamic slot name (no component change) -->
                <template v-for="res in resources" #[labelSlot(res.id)]="{ resource: r }">
                    <button
                        v-if="r.type === 'order' && hasDetails(r.order)"
                        :key="r.id"
                        type="button"
                        class="btn btn-sm btn-link p-0 text-decoration-none"
                        @click="toggle(r.order.id)"
                    >
                        <font-awesome-icon
                            :icon="isExpanded(r.order.id) ? 'chevron-down' : 'chevron-right'"
                            class="mr-1"
                        />
                        {{ isExpanded(r.order.id) ? $t('schedule.orders.hideDetails') : $t('schedule.orders.showDetails') }}
                    </button>
                    <span v-else-if="r.type === 'department'" :key="r.id" class="department-name">
                        <font-awesome-icon icon="arrow-right" class="mr-1 text-muted" />{{ r.name }}
                    </span>
                </template>

                <template #event-type-orderRange="{ event }">
                    <span class="event-label fw-semibold">{{ event.orderName }}</span>
                </template>

                <template #event-type-order="{ event }">
                    <span class="event-label fw-semibold">{{ event.orderName }}</span>
                </template>
            </ResourceCalendar>
            </BaseListing>
        </SectionBlock>

    </div>
</template>

<style scoped lang="scss">
.calendar-filter {
    width: 360px;
    margin-bottom: 0.75rem;
}

.calendar-listing {
    display: flex;
    flex-direction: column;
    flex: 1 1 auto;
    min-height: 0;
}

.department-name {
    font-size: 13px;
    color: #6c757d;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
</style>
