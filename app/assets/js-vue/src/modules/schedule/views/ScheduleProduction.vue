<script>
import Calendar from '@/components/base/Calendar'
import CellDayHoliday from '../components/CellDayHoliday'
import CellDayCapacity from '../components/CellDayCapacity'
import Sidebar from '@/components/base/Sidebar'
import CapacitySidebar from '@/modules/schedule/sidebars/CapacitySidebar'
import {fetchCapatity, fetchHolidays} from '@/modules/schedule/repository/scheduleRepository'
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
        VueSelect,
    },

    computed: {
        departmentOptions() {
            return getUserDepartments()
        },

        departmentEvents() {
            if (!this.events.capacity.length) {
                return []
            }
            return this.events.capacity
                .flatMap(e =>
                    Array.isArray(e.agreementLines)
                        ? e.agreementLines.flatMap(line =>
                            Array.isArray(line.productions)
                                ? line.productions.map(prod => ( this.filters.departmentSlug.includes(prod.departmentSlug) ? {
                                    type: 'department',
                                    id: prod.id,
                                    title: `${getDepartmentName(prod.departmentSlug)} - ${line.customerName} - ${line.productName} - ${line.orderNumber}`,
                                    resourceId: prod.departmentSlug,
                                    overlap: true,
                                    display: 'auto',
                                    start: getLocalDate(prod.dateStart),
                                    end: getLocalDate(prod.dateEnd),
                                } : null)).filter(Boolean)
                                : []
                        )
                        : []
                )
        },

        calendarEvents() {
              return [
                  ...this.events.holiday,
                  ...this.events.capacity,
                  ...this.departmentEvents,
                      { // this object will be "parsed" into an Event Object
                          title: 'The Title', // a property!
                          start: '2026-02-06', // a property!
                          end: '2026-02-11' // a property! ** see important note below about 'end' **
                      }
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
                ]).then(([holidayEvents, capacityEvents]) => {
                    this.events.holiday = holidayEvents
                    this.events.capacity = capacityEvents
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
        },
        events: {
            holiday: [],
            capacity: [],
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
        <vue-select
            :options="departmentOptions"
            :multiple="true"
            :filterable="false"
            :reduce="opt => opt.slug"
            v-model="filters.departmentSlug"
            label="name"
            placeholder="Dział produkcyjny"
            class="style-chooser"
        >
        </vue-select>

        <Calendar
            ref="calendar"
            :events="calendarEvents"
            :options="{ dayMaxEventRows: 3, selectable: false, contentHeight: 680 }"
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