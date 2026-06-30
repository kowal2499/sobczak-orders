<script>
import ResourceCalendar from "@/modules/schedule/components/ResourceCalendar/ResourceCalendar.vue";
import OrderPanelDrawer from "@/modules/agreement/components/OrderPanelDrawer.vue";
import VueSelect from 'vue-select'
import moment from 'moment'
import { fetchOrderResources, updateProductionDates, fetchHolidays } from "@/modules/schedule/repository/scheduleRepository";

// AgreementLine statuses (DELETED is filtered out server-side)
const ORDER_STATUSES = [5, 10, 15, 20]
const EDITABLE_STATUSES = ['awaits', 'started', 'in_progress']
// Compact row height so a single-bar row hugs the bar (bar is 24px tall)
const ROW_HEIGHT = 32

export default {
    name: "CalendarOrders",

    components: {
        ResourceCalendar,
        OrderPanelDrawer,
        VueSelect,
    },

    data() {
        return {
            currentMonth: moment().format('YYYY-MM'),
            loading: false,
            orders: [],
            holidays: [],
            expanded: {},
            filters: {
                statuses: [],
                agreementLineIds: [],
            },
            panelLineId: null,
            panelOpen: false,
            panelDepartment: null,
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
                        .join(' — ') || `#${o.id}`,
                    orderNumber: o.orderNumber || '',
                }))
                .sort((a, b) => String(a.orderNumber).localeCompare(String(b.orderNumber)))
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
        this.loadMonth(this.currentMonth)
    },

    methods: {
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
            this.panelLineId = event.agreementLineId
            this.panelDepartment = (event.meta && event.meta.departmentSlug) || null
            this.panelOpen = false
            this.$nextTick(() => {
                this.panelOpen = true
            })
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
    <div>
        <SectionBlockTitle block :title="$t('schedule.orders.title')" :breadcrumbs="breadcrumbs">
            <template #filters>
                <div class="row align-items-end">
                    <div class="col-md-4">
                        <label class="form-label small mb-1">{{ $t('schedule.filters.status') }}</label>
                        <vue-select
                            v-model="filters.statuses"
                            :options="statusOptions"
                            :reduce="o => o.value"
                            :placeholder="allLabel"
                            multiple
                        />
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small mb-1">{{ $t('schedule.filters.agreementLine') }}</label>
                        <vue-select
                            v-model="filters.agreementLineIds"
                            :options="agreementLineOptions"
                            :reduce="o => o.value"
                            :placeholder="allLabel"
                            multiple
                        />
                    </div>
                </div>
            </template>
        </SectionBlockTitle>

        <SectionBlock class="section-gap">
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
        </SectionBlock>

        <OrderPanelDrawer
            v-if="panelLineId"
            :key="panelLineId"
            v-model="panelOpen"
            :line-id="panelLineId"
            :active-department="panelDepartment"
            @saved="onPanelSaved"
        />
    </div>
</template>

<style scoped lang="scss">
.department-name {
    font-size: 13px;
    color: #6c757d;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
</style>
