<script>
import Calendar from '@/components/base/Calendar'
import CellDayHoliday from '../components/CellDayHoliday'
import CellDayCapacity from '../components/CellDayCapacity'
import Sidebar from '@/components/base/Sidebar'
import CapacitySidebar from '@/modules/schedule/sidebars/CapacitySidebar'
import {fetchCapatity, fetchHolidays, fetchAgreementLines} from '@/modules/schedule/repository/scheduleRepository'
import {v4 as uuidv4} from 'uuid';
import {getDepartmentName, getUserDepartments, orderDisplayNumber} from "@/helpers";
import VueSelect from 'vue-select'
import { describeSelection } from "@/modules/schedule/filters/describeSelection";
import FiltersDropdown from "@/components/base/FiltersDropdown.vue";
import BaseListing from "@/components/base/BaseListing/index.vue";
import Listing from "@/components/base/BaseListing/model/Listing.js";
import Criterion, { TYPE_LIST } from "@/components/base/BaseListing/model/Criterion.js";

const LISTING_ID = 'calendar-general'

export default {
    name: "CalendarGeneral",

    components: {
        Calendar,
        CellDayHoliday,
        CellDayCapacity,
        Sidebar,
        CapacitySidebar,
        VueSelect,
        FiltersDropdown,
        BaseListing,
    },

    computed: {
        breadcrumbs() {
            return [
                { icon: 'home', href: '/', label: this.$t('schedule.breadcrumb.home') },
                { label: this.$t('schedule.generalTitle') },
            ]
        },

        allLabel() {
            return this.$t('schedule.all')
        },

        departmentOptions() {
            return getUserDepartments().map(d => ({ value: d.slug, label: d.name }))
        },

        customerOptions() {
            const options = this.events.departments.map(line => ({ value: line.customer.id, label: line.customer.name }))
            const unique = Array.from(new Map(options.map(o => [o.value, o])).values())
            return unique.sort((a, b) => a.label.localeCompare(b.label))
        },

        agreementLineOptions() {
            const { customerId } = this.filters
            return this.events.departments
                .filter(line => customerId.length ? customerId.includes(line.customer.id) : true)
                .map(line => ({
                    value: line.agreementLineId,
                    label: `${orderDisplayNumber(line.orderNumber, line.internalNumber)} - ${line.productName}`,
                }))
                .sort((a, b) => b.value - a.value)
        },

        knownCustomerOptions() {
            return Object.keys(this.known.customers).map(id => ({ value: Number(id), label: this.known.customers[id] }))
        },

        knownOrderOptions() {
            return Object.keys(this.known.orders).map(id => ({ value: Number(id), label: this.known.orders[id] }))
        },

        filters() {
            const values = (this.listing && this.listing.criteriaValues) || {}
            return {
                departmentSlug: values.departmentSlug || [],
                customerId: values.customerId || [],
                agreementLineId: values.agreementLineId || [],
            }
        },

        activeFilterCount() {
            return this.listing ? this.listing.activeCriteria.length : 0
        },

        calendarOptions() {
            return {
                dayMaxEventRows: 4,
                selectable: false,
                weekNumbers: true,
                weekNumberFormat: { week: 'numeric' },
                // fill the page on wider screens; the narrow layout scrolls the page instead
                height: this.fillHeight ? '100%' : 'auto',
            }
        },

        filteredDepartmentEvents() {
            return this.events.departments
                .filter(line => this.filters.customerId.length
                    ? this.filters.customerId.includes(line.customer.id)
                    : true)
                .filter(line => this.filters.agreementLineId.length
                    ? this.filters.agreementLineId.includes(line.agreementLineId)
                    : true)
                .flatMap(line => Array.isArray(line.productions)
                    ? line.productions.map(prod => ({
                        type: 'department',
                        id: prod.id,
                        start: prod.dateStart,
                        end: prod.dateEnd,
                        departmentSlug: prod.departmentSlug,
                        title: `${getDepartmentName(prod.departmentSlug)} - ${line.customerName} - ${line.productName} - ${orderDisplayNumber(line.orderNumber, line.internalNumber)}`,
                        resourceId: prod.departmentSlug,
                        overlap: true,
                        allDay: true,
                        display: 'auto',
                    }))
                    : [])
                .filter(event => this.filters.departmentSlug.length
                    ? this.filters.departmentSlug.includes(event.departmentSlug)
                    : true
                )
        },

        calendarEvents() {
          return [
              ...this.events.holiday,
              ...this.events.capacity,
              ...this.filteredDepartmentEvents,
          ]
        }
    },

    watch: {
        range: {
            async handler() {
                if (!this.range.start || !this.range.end) {
                    return
                }
                if (this.range.start === this.lastDate.start && this.range.end === this.lastDate.end) {
                    return
                }

                if (false === this.$user.can('reports.calendar_general')) {
                    return
                }

                this.lastDate = { ...this.range }

                await this.loadEvents()
            },
            deep: true,
        },
    },

    created() {
        this.fillHeight = window.matchMedia('(min-width: 768px)').matches
        this.listing = new Listing(LISTING_ID, this.$t('schedule.generalTitle'), [], this.buildCriteria())
        this.listing.onPersistError(() => this.$flash.danger(this.$t('listing.saveError')))
        this.listing.fetchViews()
        EventBus.$on('agreementLineSaved', this.reloadAfterSave)
    },

    beforeDestroy() {
        EventBus.$off('agreementLineSaved', this.reloadAfterSave)
    },

    methods: {
        buildCriteria() {
            const department = this.$t('schedule.filters.department')
            const customer = this.$t('schedule.filters.customer')
            const order = this.$t('schedule.filters.agreementLine')

            return [
                new Criterion({
                    id: 'departmentSlug',
                    label: department,
                    type: TYPE_LIST,
                    defaultValue: [],
                    formatter: slugs => describeSelection(department, slugs, this.departmentOptions),
                }),
                new Criterion({
                    id: 'customerId',
                    label: customer,
                    type: TYPE_LIST,
                    defaultValue: [],
                    formatter: ids => describeSelection(customer, ids, this.knownCustomerOptions),
                }),
                new Criterion({
                    id: 'agreementLineId',
                    label: order,
                    type: TYPE_LIST,
                    defaultValue: [],
                    formatter: ids => describeSelection(order, ids, this.knownOrderOptions),
                }),
            ]
        },

        setFilter(id, value) {
            this.listing.setCriterionValue(id, value)
            // the order list is narrowed to the chosen customers, so a stale order choice must go
            if (id === 'customerId' && this.filters.agreementLineId.length) {
                this.listing.setCriterionValue('agreementLineId', [])
            }
        },

        rememberLines(lines) {
            for (const line of lines) {
                if (line.customer && !this.known.customers[line.customer.id]) {
                    this.$set(this.known.customers, line.customer.id, line.customer.name)
                }
                if (!this.known.orders[line.agreementLineId]) {
                    this.$set(this.known.orders, line.agreementLineId, orderDisplayNumber(line.orderNumber, line.internalNumber))
                }
            }
        },

        async loadEvents() {
            const [holidayEvents, capacityEvents, departmentEvents] = await Promise.all([
                this.fetchHolidayEvents(this.range),
                this.fetchCapacityEvents(this.range),
                this.fetchDepartmentEvents(this.range)
            ])
            this.events.holiday = holidayEvents
            this.events.capacity = capacityEvents
            this.events.departments = departmentEvents
            this.rememberLines(departmentEvents)
        },

        async reloadAfterSave() {
            if (!this.range.start || !this.range.end) {
                return
            }
            await this.loadEvents()
            this.refreshSidebarEvents()
        },

        // sidebar trzyma migawkę zdarzeń z chwili kliknięcia - podmieniamy ją na świeże z tego samego dnia
        refreshSidebarEvents() {
            const dateKey = this.sidebar.data?.events?.capacity?.[0]?.dateKey
            if (!dateKey) {
                return
            }
            const fresh = this.events.capacity.find(event => event.dateKey === dateKey)
            this.sidebar.data = {
                ...this.sidebar.data,
                events: { ...this.sidebar.data.events, capacity: fresh ? [fresh] : [] },
            }
        },

        onDateSet(data) {
            this.range.start = data.start
            this.range.end = data.end
        },

        async fetchHolidayEvents({ start, end }) {
            const { data } = await fetchHolidays(start, end)
            return data.map(event => ({
                identifier: event.id,
                dayType: event.dayType,
                type: 'holiday',
                dateKey: event.date,
                date: event.date,
                description: event.description,

                id: uuidv4(),
                start: (new Date(`${event.date}T23:59:59`)),
                end: (new Date(`${event.date}T00:00:00`)),
                allDay: true,
                display: 'background',
            }))
        },

        async fetchCapacityEvents({ start, end }) {
            const { data } = await fetchCapatity(start, end)
            return data.map(event => ({
                type: 'capacity',
                dateKey: event.date,
                capacity: event.capacity,
                capacityBurned: event.capacityBurned,
                agreementLines: event.agreementLines,

                id: uuidv4(),
                start: (new Date(`${event.date}T23:59:59`)),
                end: (new Date(`${event.date}T00:00:00`)),
                allDay: true,
                display: 'none'
            }))
        },

        async fetchDepartmentEvents({ start, end }) {
            const { data } = await fetchAgreementLines(start, end)
            return data
        },

        showSidebar(type, { arg, events }) {
            this.sidebar.data = { arg, events }
            switch (type) {
                case 'capacity':
                    this.sidebar.component = () => import('../sidebars/CapacitySidebar.vue')
                    break
                default:
                    this.sidebar.component = null
            }
            this.sidebar.isOpen = true
        },
    },

    data: () => ({
        listing: null,
        fillHeight: true,
        range: {
            start: null,
            end: null,
        },
        // names seen this session, so a chip still names a customer or order outside the range
        known: {
            customers: {},
            orders: {},
        },
        events: {
            holiday: [],
            capacity: [],
            departments: [],
        },
        sidebar: {
            data: null,
            title: '',
            isOpen: false,
            component: null
        },
        selectedData: null,
        lastDate: {
            start: null,
            end: null,
        }
    }),
}
</script>

<template>
    <div class="schedule-production page-fill">
        <SectionBlockTitle block :title="$t('schedule.generalTitle')" :breadcrumbs="breadcrumbs">
            <template #actions>
                <b-nav>
                    <FiltersDropdown :count="activeFilterCount" @clear="listing.clearCriteria()">
                        <div class="calendar-filter">
                            <label class="form-label small mb-1">{{ $t('schedule.filters.department') }}</label>
                            <vue-select
                                :value="filters.departmentSlug"
                                @input="setFilter('departmentSlug', $event)"
                                :options="departmentOptions"
                                :reduce="o => o.value"
                                :filterable="false"
                                :placeholder="allLabel"
                                multiple
                            />
                        </div>
                        <div class="calendar-filter">
                            <label class="form-label small mb-1">{{ $t('schedule.filters.customer') }}</label>
                            <vue-select
                                :value="filters.customerId"
                                @input="setFilter('customerId', $event)"
                                :options="customerOptions"
                                :reduce="o => o.value"
                                :placeholder="allLabel"
                                multiple
                            />
                        </div>
                        <div class="calendar-filter">
                            <label class="form-label small mb-1">{{ $t('schedule.filters.agreementLine') }}</label>
                            <vue-select
                                :value="filters.agreementLineId"
                                @input="setFilter('agreementLineId', $event)"
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
        <Calendar
            ref="calendar"
            class="calendar-listing__calendar"
            :events="calendarEvents"
            :options="calendarOptions"
            @date-set="onDateSet"
        >
            <template #day-cell-content-holiday="{ arg, events }">
                <CellDayHoliday :arg="arg" :events="events" />
            </template>

            <template #day-cell-content-capacity="{ arg, events }">
                <CellDayCapacity :arg="arg" :events="events" :hasSelect="false" @daySelected="showSidebar('capacity', { arg, events: [ $event ] })"/>
            </template>

            <template #day-cell-dropdown="{ arg, events }">
                <b-dropdown-item-button @click="showSidebar('capacity', { arg, events })">Szczegóły obłożenia</b-dropdown-item-button>
            </template>
        </Calendar>
        </BaseListing>
        </SectionBlock>

        <Sidebar v-model="sidebar.isOpen" :title="sidebar.title" sidebar-class="size-100 size-lg-75 size-xxl-50">
            <template #sidebar-content>
                <component
                    v-if="sidebar.component"
                    :is="sidebar.component"
                    :data="sidebar.data"
                    @set-title="sidebar.title = $event"
                />
            </template>
        </Sidebar>
    </div>
</template>

<style lang="scss">
.schedule-production {
    .fc-daygrid-week-number {
        top: 25px;
    }

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

    .calendar-listing__calendar {
        flex: 1 1 auto;
        min-height: 0;
    }
}
</style>