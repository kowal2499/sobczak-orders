<script>
import Calendar from '@/components/base/Calendar'
import CellDayHoliday from '../components/CellDayHoliday'
import CellDayCapacity from '../components/CellDayCapacity'
import ScheduleProductionFilters from '../filters/ScheduleProductionFilters'
import Sidebar from '@/components/base/Sidebar'
import CapacitySidebar from '@/modules/schedule/sidebars/CapacitySidebar'
import {fetchCapatity, fetchHolidays, fetchAgreementLines} from '@/modules/schedule/repository/scheduleRepository'
import {v4 as uuidv4} from 'uuid';
import {getLocalDate, getDepartmentName, getUserDepartments} from "@/helpers";
import VueSelect from 'vue-select'

export default {
    name: "ScheduleProduction",

    components: {
        Calendar,
        CellDayHoliday,
        CellDayCapacity,
        Sidebar,
        CapacitySidebar,
        ScheduleProductionFilters,
        VueSelect,
    },

    computed: {
        departmentOptions() {
            return getUserDepartments()
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
                        title: `${getDepartmentName(prod.departmentSlug)} - ${line.customerName} - ${line.productName} - ${line.orderNumber}`,
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
        'filters.date': {
            async handler() {
                if (!this.filters.date.start || !this.filters.date.end) {
                    return
                }

                await Promise.all([
                    this.fetchHolidayEvents(this.filters.date),
                    this.fetchCapacityEvents(this.filters.date),
                    this.fetchDepartmentEvents(this.filters.date)
                ]).then(([holidayEvents, capacityEvents, departmentEvents]) => {
                    this.events.holiday = holidayEvents
                    this.events.capacity = capacityEvents
                    this.events.departments = departmentEvents
                })
            },
            deep: true,
        },
    },

    methods: {
        onDateSet(data) {
            this.filters.date.start = data.start
            this.filters.date.end = data.end
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
        filters: {
            date: {
                start: null,
                end: null,
            },
            agreementLineId: [],
            departmentSlug: [],
            customerId: [],
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
        q: '',
        selectedData: null,
        sidebarTitle: '',
        isSidebarOpen: false,
    }),
}
</script>

<template>
    <div>
        <ScheduleProductionFilters
            :agreementLines="events.departments"
            v-model="filters"
            class="mb-3"
        />

        <Calendar
            ref="calendar"
            :events="calendarEvents"
            :options="{ dayMaxEventRows: 4, selectable: false, /* contentHeight: 680 */ }"
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
        <Sidebar v-model="sidebar.isOpen" :title="sidebar.title" sidebar-class="size-75 size-lg-50">
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

<style lang="scss" scoped>


</style>