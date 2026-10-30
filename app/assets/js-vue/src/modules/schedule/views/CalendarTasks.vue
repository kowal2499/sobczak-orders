<script>
import ResourceCalendar from "@/modules/schedule/components/ResourceCalendar/ResourceCalendar.vue";
import { openOrderPanel } from "@/modules/agreement/orderPanel";
import VueSelect from 'vue-select'
import moment from 'moment'
import { fetchProductionResources, updateProductionDates, fetchHolidays } from "@/modules/schedule/repository/scheduleRepository";
import { STATUS_COLORS } from "@/modules/schedule/components/ResourceCalendar/utils/gridHelpers";
import { describeSelection } from "@/modules/schedule/filters/describeSelection";
import FiltersDropdown from "@/components/base/FiltersDropdown.vue";
import BaseListing from "@/components/base/BaseListing/index.vue";
import Listing from "@/components/base/BaseListing/model/Listing.js";
import Criterion, { TYPE_BOOLEAN, TYPE_LIST } from "@/components/base/BaseListing/model/Criterion.js";

const LISTING_ID = 'calendar-tasks'
const STATUS_OPTIONS = ['awaits', 'started', 'in_progress', 'completed', 'not_applicable']
const EDITABLE_STATUSES = ['awaits', 'started', 'in_progress']

export default {
    name: "CalendarTasks",

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
            ready: false,
            loadedGhost: null,
            loading: false,
            rawResources: [],
            rawEvents: [],
            holidays: [],
            // order numbers seen this session, so a chip still names an order outside the month
            knownOrders: {},
        }
    },

    computed: {
        breadcrumbs() {
            return [
                { icon: 'home', href: '/', label: this.$t('schedule.breadcrumb.home') },
                { label: this.$t('schedule.title') },
            ]
        },

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
                    .join(' - ')
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

        knownOrderOptions() {
            return Object.keys(this.knownOrders).map(id => ({ value: Number(id), label: this.knownOrders[id] }))
        },

        filters() {
            const values = (this.listing && this.listing.criteriaValues) || {}
            return {
                departments: values.departments || [],
                statuses: values.statuses || [],
                agreementLineIds: values.agreementLineIds || [],
            }
        },

        includeGhost() {
            return Boolean(this.listing && this.listing.criteriaValues.ghost)
        },

        activeFilterCount() {
            return this.listing ? this.listing.activeCriteria.length : 0
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

    watch: {
        includeGhost(value) {
            if (this.ready && value !== this.loadedGhost) {
                this.loadMonth(this.currentMonth)
            }
        },
    },

    created() {
        this.listing = new Listing(LISTING_ID, this.$t('schedule.title'), [], this.buildCriteria())
        this.listing.onPersistError(() => this.$flash.danger(this.$t('listing.saveError')))
        this.listing.fetchViews().finally(() => {
            this.ready = true
            this.loadMonth(this.currentMonth)
        })
    },

    mounted() {
        window.EventBus.$on('agreementLineSaved', this.onPanelSaved)
    },

    beforeDestroy() {
        window.EventBus.$off('agreementLineSaved', this.onPanelSaved)
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
            this.loadedGhost = this.includeGhost
            this.loadHolidays(start, end)
            return fetchProductionResources(start, end, this.includeGhost)
                .then(({ data }) => {
                    this.rawResources = data.resources || []
                    this.rawEvents = data.events || []
                    this.rememberOrders(this.rawEvents)
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

        buildCriteria() {
            const department = this.$t('schedule.filters.department')
            const status = this.$t('schedule.filters.status')
            const order = this.$t('schedule.filters.agreementLine')

            return [
                new Criterion({
                    id: 'departments',
                    label: department,
                    type: TYPE_LIST,
                    defaultValue: [],
                    formatter: ids => describeSelection(department, ids, this.departmentOptions),
                }),
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
                new Criterion({
                    id: 'ghost',
                    label: this.$t('schedule.filters.ghost'),
                    type: TYPE_BOOLEAN,
                    defaultValue: false,
                }),
            ]
        },

        setFilter(id, value) {
            this.listing.setCriterionValue(id, value)
        },

        rememberOrders(events) {
            for (const e of events) {
                const orderNumber = e.meta && e.meta.orderNumber
                if (orderNumber && !this.knownOrders[e.agreementLineId]) {
                    this.$set(this.knownOrders, e.agreementLineId, orderNumber)
                }
            }
        },

        canEditEvent(event) {
            return this.$user.can('production.panel')
                && EDITABLE_STATUSES.includes(event.orderStatus)
        },

        onEventClick(event) {
            openOrderPanel(this.$router, event.agreementLineId, event.resourceId)
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
    <div class="page-fill">
        <SectionBlockTitle block :title="$t('schedule.title')" :breadcrumbs="breadcrumbs">
            <template #actions>
                <b-nav>
                    <FiltersDropdown :count="activeFilterCount" @clear="listing.clearCriteria()">
                        <div class="calendar-filter">
                            <label class="form-label small mb-1">{{ $t('schedule.filters.department') }}</label>
                            <vue-select
                                :value="filters.departments"
                                @input="setFilter('departments', $event)"
                                :options="departmentOptions"
                                :reduce="o => o.value"
                                :placeholder="allLabel"
                                multiple
                            />
                        </div>
                        <div class="calendar-filter">
                            <label class="form-label small mb-1">{{ $t('schedule.filters.status') }}</label>
                            <vue-select
                                :value="filters.statuses"
                                @input="setFilter('statuses', $event)"
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
                        <b-form-checkbox
                            :checked="includeGhost"
                            switch
                            size="sm"
                            @change="setFilter('ghost', $event)"
                        >
                            {{ $t('schedule.showGhost') }}
                        </b-form-checkbox>
                    </FiltersDropdown>
                </b-nav>
            </template>
        </SectionBlockTitle>

        <SectionBlock class="section-gap page-fill__body">
        <BaseListing class="calendar-listing" :listing-configuration="listing">
        <ResourceCalendar
            :resources="filteredResources"
            :events="filteredEvents"
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
            <template #event-type-order="{ event }">
                <span class="event-label fw-semibold">
                    {{ event.orderName }}
                </span>
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
