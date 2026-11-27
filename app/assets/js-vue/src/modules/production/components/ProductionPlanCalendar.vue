<template>
    <div class="plan-calendar">
        <div class="plan-calendar__toolbar">
            <vue-select
                v-model="selectedDepartments"
                class="plan-calendar__departments"
                :options="departmentOptions"
                :reduce="o => o.value"
                :placeholder="$t('production.planCalendar.allDepartments')"
                multiple
            />
            <div class="plan-calendar__switches">
                <b-form-checkbox v-model="showHeatmap" switch size="sm">
                    {{ $t('production.planCalendar.showHeatmap') }}
                </b-form-checkbox>
                <b-form-checkbox v-model="showExisting" switch size="sm">
                    {{ $t('production.planCalendar.showExistingTasks') }}
                </b-form-checkbox>
            </div>
        </div>

        <b-overlay :show="loading" rounded="sm">
            <ResourceCalendar
                :resources="resources"
                :events="events"
                :holidays="holidays"
                :range="range"
                :show-header="false"
                :interactive="true"
                :allow-create="false"
                :can-edit-event="isPlanEvent"
                :cell-title="cellTitle"
                :week-header="weekHeader"
                :markers="markers"
                @event-moved="onEventChanged"
                @event-resized="onEventChanged"
            />
        </b-overlay>
    </div>
</template>

<script>
import moment from 'moment'
import VueSelect from 'vue-select'
import ResourceCalendar from '@/modules/schedule/components/ResourceCalendar/ResourceCalendar.vue'
import {
    fetchCapatity,
    fetchDepartmentLoad,
    fetchHolidays,
    fetchProductionResources,
} from '@/modules/schedule/repository/scheduleRepository'
import helpers, { getDepartmentName } from '@/helpers'
import {
    applyEventToForm,
    capacityByWeek,
    defaultRange,
    isPlanEvent,
    loadToBackgroundEvents,
    onlyDepartments,
    planToEvents,
    weekHeaderCell,
} from '../services/productionPlanCalendar'

const EXISTING_TASK_COLOR = '#e9ecef'

export default {
    name: 'ProductionPlanCalendar',

    components: { ResourceCalendar, VueSelect },

    props: {
        value: { type: Array, default: () => [] },
        confirmedDate: { type: String, required: true },
        agreementLineId: { type: Number, default: null },
    },

    data: () => ({
        departments: helpers.getDepartments().map(d => ({ id: d.slug, name: d.name, hasAccess: true })),
        load: {},
        capacity: {},
        holidays: [],
        existingEvents: [],
        showExisting: false,
        showHeatmap: false,
        selectedDepartments: [],
        loading: false,
        lastRequestId: 0,
        lastExistingRequestId: 0,
    }),

    computed: {
        range() {
            return defaultRange(this.value, this.confirmedDate, moment().format('YYYY-MM-DD'))
        },

        markers() {
            return [{ date: this.confirmedDate }]
        },

        rangeKey() {
            return `${this.range.start}|${this.range.end}`
        },

        departmentOptions() {
            return this.departments.map(d => ({ value: d.id, label: d.name }))
        },

        resources() {
            return onlyDepartments(this.departments, this.selectedDepartments, d => d.id).map(d => ({
                id: d.id,
                name: d.name,
                note: d.hasAccess ? null : this.$t('production.planCalendar.noAccess'),
            }))
        },

        events() {
            const existing = this.showExisting
                ? this.existingEvents
                    .filter(e => e.agreementLineId !== this.agreementLineId)
                    .map(e => ({ ...e, color: EXISTING_TASK_COLOR }))
                : []

            return onlyDepartments([
                ...(this.showHeatmap ? loadToBackgroundEvents(this.load) : []),
                ...existing,
                ...planToEvents(this.value, getDepartmentName),
            ], this.selectedDepartments, e => e.resourceId)
        },
    },

    watch: {
        rangeKey: {
            immediate: true,
            handler() {
                this.fetchData()
            },
        },

        showExisting(value) {
            if (value) {
                this.fetchExisting()
            }
        },
    },

    methods: {
        isPlanEvent,

        weekHeader(weekStart) {
            const week = this.capacity[weekStart]
            const cell = weekHeaderCell(weekStart, week)
            const title = week
                ? this.$t('production.planCalendar.capacityTitle', {
                    burned: Math.round(week.burned * 100) / 100,
                    capacity: Math.round(week.capacity * 100) / 100,
                })
                : ''
            return { ...cell, title }
        },

        cellTitle(resourceId, date) {
            if (!this.showHeatmap) {
                return ''
            }
            const count = (this.load[resourceId] || {})[date]
            return count ? this.$t('production.planCalendar.loadTitle', { count }) : ''
        },

        onEventChanged({ updated }) {
            if (!isPlanEvent(updated)) {
                return
            }
            this.$emit('input', applyEventToForm(this.value, updated))
        },

        fetchData() {
            const requestId = ++this.lastRequestId
            const { start, end } = this.range
            this.loading = true

            const requests = [
                fetchDepartmentLoad(start, end, this.agreementLineId),
                fetchHolidays(start, end),
                fetchCapatity(start, end).catch(() => ({ data: [] })),
            ]

            Promise.all(requests)
                .then(([loadResponse, holidaysResponse, capacityResponse]) => {
                    if (requestId !== this.lastRequestId) {
                        return
                    }
                    const asMap = value => (Array.isArray(value) ? {} : (value || {}))
                    this.departments = loadResponse.data.departments || []
                    this.load = asMap(loadResponse.data.load)
                    this.capacity = capacityByWeek(capacityResponse.data)
                    this.holidays = (holidaysResponse.data || [])
                        .filter(h => h.date)
                        .map(h => ({ date: h.date, description: h.description || '' }))
                })
                .catch(() => {
                    window.EventBus.$emit('message', {
                        type: 'error',
                        content: this.$t('production.planCalendar.loadError'),
                    })
                })
                .finally(() => {
                    if (requestId === this.lastRequestId) {
                        this.loading = false
                    }
                })

            if (this.showExisting) {
                this.fetchExisting()
            }
        },

        fetchExisting() {
            const requestId = ++this.lastExistingRequestId
            const { start, end } = this.range
            return fetchProductionResources(start, end)
                .then(({ data }) => {
                    if (requestId === this.lastExistingRequestId) {
                        this.existingEvents = data.events || []
                    }
                })
                .catch(() => {
                    this.existingEvents = []
                })
        },
    },
}
</script>

<style scoped lang="scss">
.plan-calendar {
    &__toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        margin-bottom: 0.5rem;
    }

    &__departments {
        min-width: 280px;
        max-width: 100%;
    }

    &__switches {
        display: flex;
        flex-wrap: wrap;
        gap: 0.35rem 1.25rem;
    }
}
</style>
