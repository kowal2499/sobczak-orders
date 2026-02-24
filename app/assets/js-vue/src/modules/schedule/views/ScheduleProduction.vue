<script>
import Calendar from '@/components/base/Calendar.vue'
import CellDayHoliday from '../components/CellDayHoliday.vue'
import CellDayCapacity from '../components/CellDayCapacity.vue'
import { fetchCapatity, fetchHolidays } from '@/modules/schedule/repository/scheduleRepository'
import Sidebar from '@/components/base/Sidebar'
import { getLocalDate } from '@/helpers'
import AgreementLineRmShowcaseItem from '@/components/base/Showcase/AgreementLineRmShowcaseItem'
export default {
    name: "ScheduleProduction",

    components: { Calendar, CellDayHoliday, CellDayCapacity, Sidebar, AgreementLineRmShowcaseItem },

    methods: {
        async eventsProvider(start, end) {
            const events = [];

            const promises = [
                fetchHolidays(start, end).then(({data}) => {
                    data.forEach(event => {
                        events.push({
                            identifier: event.id,
                            dayType: event.dayType,
                            type: 'holiday',
                            dateKey: event.date,
                            description: event.description,

                            start: (new Date(`${event.date}T23:59:59`)),
                            end: (new Date(`${event.date}T00:00:00`)),
                            allDay: true,
                            display: 'background',
                        })
                    })
                }),
                fetchCapatity(start, end).then(({data}) => {
                    data.forEach(event => {
                        events.push({
                            type: 'capacity',
                            dateKey: event.date,
                            capacity: event.capacity,
                            capacityBurned: event.capacityBurned,
                            agreementLines: event.agreementLines,
                            start: (new Date(`${event.date}T23:59:59`)),
                            end: (new Date(`${event.date}T00:00:00`)),
                            allDay: true,
                            display: 'none'
                        })
                    })
                })
            ]
            await Promise.all(promises)
            return events
        },

        showSidebar({ arg, events }) {
            this.selectedData = { arg, events }
            this.isSidebarOpen = true
            this.sidebarTitle = `Szczegóły - ${getLocalDate(arg.date)}`
        },

    },

    data: () => ({
        selectedData: null,
        sidebarTitle: '',
        isSidebarOpen: false,
    }),
}
</script>

<template>
    <div>
        <Calendar
            ref="calendar"
            :eventsProvider="eventsProvider"
            :options="{ selectable: false, contentHeight: 680 }"
        >
            <template #day-cell-content-holiday="{ arg, events }">
                <CellDayHoliday :arg="arg" :events="events" />
            </template>

            <template #day-cell-content-capacity="{ arg, events }">
                <CellDayCapacity :arg="arg" :events="events" :hasSelect="false" @daySelected="selectedData = $event"/>
            </template>

            <template #day-cell-dropdown="{ arg, events }">
                <b-dropdown-item-button @click="showSidebar({ arg, events })">Szczegóły</b-dropdown-item-button>
            </template>
        </Calendar>
        <Sidebar v-model="isSidebarOpen" :title="sidebarTitle" sidebar-class="size-75 size-lg-50">
            <template #sidebar-content>
                <AgreementLineRmShowcaseItem
                    v-for="line in selectedData.events.capacity?.[0].agreementLines || []"
                    :key="line.id"
                    :data="line"
                />
            </template>
        </Sidebar>
    </div>
</template>

<style lang="scss" scoped>


</style>