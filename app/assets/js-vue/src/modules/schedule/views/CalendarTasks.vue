<script>
import ResourceCalendar from "@/modules/schedule/components/ResourceCalendar/ResourceCalendar.vue";
import OrderPanelDrawer from "@/modules/agreement/components/OrderPanelDrawer.vue";
import VueSelect from 'vue-select'
import moment from 'moment'
import { fetchProductionResources, updateProductionDates } from "@/modules/schedule/repository/scheduleRepository";
import { STATUS_COLORS } from "@/modules/schedule/components/ResourceCalendar/utils/gridHelpers";

const STATUS_OPTIONS = ['pending', 'in_progress', 'completed', 'cancelled']
const EDITABLE_STATUSES = ['pending', 'in_progress']

export default {
    name: "CalendarTasks",

    components: {
        ResourceCalendar,
        OrderPanelDrawer,
        VueSelect,
    },

    data() {
        return {
            currentMonth: moment().format('YYYY-MM'),
            includeGhost: false,
            loading: false,
            rawResources: [],
            rawEvents: [],
            filters: {
                departments: [],
                statuses: [],
                agreementLineIds: [],
            },
            panelLineId: null,
            panelOpen: false,
        }
    },

    computed: {
        allLabel() {
            return this.$t('schedule.all')
        },

        departmentOptions() {
            return this.rawResources.map(r => ({ value: r.id, label: r.name }))
        },

        statusOptions() {
            return STATUS_OPTIONS.map(s => ({
                value: s,
                label: this.$t(`schedule.status.${s}`),
                color: (STATUS_COLORS[s] && STATUS_COLORS[s].bg) || '#e9ecef',
                borderColor: (STATUS_COLORS[s] && STATUS_COLORS[s].border) || '#adb5bd',
            }))
        },

        agreementLineOptions() {
            const seen = new Map()
            for (const e of this.rawEvents) {
                if (seen.has(e.agreementLineId)) continue
                const m = e.meta || {}
                const label = [m.orderNumber, m.customerName, m.productName]
                    .filter(Boolean)
                    .join(' — ')
                seen.set(e.agreementLineId, {
                    value: e.agreementLineId,
                    label: label || `#${e.agreementLineId}`,
                    orderNumber: m.orderNumber || '',
                })
            }
            return [...seen.values()].sort((a, b) =>
                String(a.orderNumber).localeCompare(String(b.orderNumber))
            )
        },

        filteredEvents() {
            const { departments, statuses, agreementLineIds } = this.filters
            return this.rawEvents.filter(e => {
                if (departments.length && !departments.includes(e.resourceId)) return false
                if (statuses.length && !statuses.includes(e.orderStatus)) return false
                if (agreementLineIds.length && !agreementLineIds.includes(e.agreementLineId)) return false
                return true
            })
        },

        filteredResources() {
            const { departments } = this.filters
            if (!departments.length) return this.rawResources
            const set = new Set(departments)
            return this.rawResources.filter(r => set.has(r.id))
        },
    },

    created() {
        this.loadMonth(this.currentMonth)
    },

    methods: {
        rangeFor(month) {
            const m = moment(month, 'YYYY-MM')
            return {
                start: m.clone().startOf('month').format('YYYY-MM-DD'),
                end: m.clone().endOf('month').format('YYYY-MM-DD'),
            }
        },

        loadMonth(month) {
            if (false === this.$user.can('reports.calendar_tasks')) {
                return Promise.resolve()
            }
            const { start, end } = this.rangeFor(month)
            this.loading = true
            return fetchProductionResources(start, end, this.includeGhost)
                .then(({ data }) => {
                    this.rawResources = data.resources || []
                    this.rawEvents = data.events || []
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

        onMonthChange(month) {
            this.currentMonth = month
            this.loadMonth(month)
        },

        onGhostToggle() {
            this.loadMonth(this.currentMonth)
        },

        canEditEvent(event) {
            return this.$user.can('production.panel')
                && EDITABLE_STATUSES.includes(event.orderStatus)
        },

        onEventClick(event) {
            this.panelLineId = event.agreementLineId
            this.panelOpen = false
            this.$nextTick(() => {
                this.panelOpen = true
            })
        },

        onEventMoved({ previous, updated }) {
            if (
                previous.dateStart === updated.dateStart
                && previous.dateEnd === updated.dateEnd
                && previous.resourceId === updated.resourceId
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
                    const target = this.rawEvents.find(e => e.id === event.id)
                    if (target) {
                        target.dateStart = event.dateStart
                        target.dateEnd = event.dateEnd
                    }
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

        onPanelSaved() {
            this.loadMonth(this.currentMonth)
        },
    },
}
</script>

<template>
    <div>
        <div class="row align-items-end mb-3">
            <div class="col-md-3">
                <label class="form-label small mb-1">{{ $t('schedule.filters.department') }}</label>
                <vue-select
                    v-model="filters.departments"
                    :options="departmentOptions"
                    :reduce="o => o.value"
                    :placeholder="allLabel"
                    multiple
                />
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">{{ $t('schedule.filters.status') }}</label>
                <vue-select
                    v-model="filters.statuses"
                    :options="statusOptions"
                    :reduce="o => o.value"
                    :placeholder="allLabel"
                    multiple
                >
                    <template #option="{ label, color, borderColor }">
                        <span class="status-swatch" :style="{ background: color, borderColor }" />
                        <span>{{ label }}</span>
                    </template>
                    <template #selected-option="{ label, color, borderColor }">
                        <span class="status-swatch" :style="{ background: color, borderColor }" />
                        <span>{{ label }}</span>
                    </template>
                </vue-select>
            </div>
            <div class="col-md-4">
                <label class="form-label small mb-1">{{ $t('schedule.filters.agreementLine') }}</label>
                <vue-select
                    v-model="filters.agreementLineIds"
                    :options="agreementLineOptions"
                    :reduce="o => o.value"
                    :placeholder="allLabel"
                    multiple
                />
            </div>
            <div class="col-md-2">
                <b-form-checkbox
                    :checked="includeGhost"
                    switch
                    size="sm"
                    @change="val => { includeGhost = val; onGhostToggle() }"
                >
                    {{ $t('schedule.showGhost') }}
                </b-form-checkbox>
            </div>
        </div>

        <ResourceCalendar
            :resources="filteredResources"
            :events="filteredEvents"
            :value="currentMonth"
            :interactive="true"
            :allow-create="false"
            :can-edit-event="canEditEvent"
            @month-change="onMonthChange"
            @event-click="onEventClick"
            @event-moved="onEventMoved"
            @event-resized="onEventResized"
        >
            <template #event-type-order="{ event }">
                <span class="event-label fw-semibold">
                    {{ event.orderName }}
                </span>
            </template>
        </ResourceCalendar>

        <OrderPanelDrawer
            v-if="panelLineId"
            :key="panelLineId"
            v-model="panelOpen"
            :line-id="panelLineId"
            @saved="onPanelSaved"
        />
    </div>
</template>

<style scoped lang="scss">
.status-swatch {
    display: inline-block;
    width: 12px;
    height: 12px;
    margin-right: 6px;
    border-radius: 2px;
    border: 1px solid;
    vertical-align: middle;
}
</style>
