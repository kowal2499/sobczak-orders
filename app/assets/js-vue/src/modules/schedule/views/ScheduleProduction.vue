<script>
import Calendar from '@/components/base/Calendar.vue'
import CellDayHoliday from '../components/CellDayHoliday.vue'
import CellDayCapacity from '../components/CellDayCapacity.vue'
import { fetchCapatity, fetchHolidays } from '@/modules/schedule/repository/scheduleRepository'

export default {
    name: "ScheduleProduction",

    components: { Calendar, CellDayHoliday, CellDayCapacity },

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
    },

    data: () => ({
        selectedDayEvent: null,
    }),
}
</script>

<template>
    <Calendar
        ref="calendar"
        :eventsProvider="eventsProvider"
        :options="{ selectable: false, contentHeight: 680 }"
    >
        <template #day-cell-content-holiday="{ arg, events }">
            <CellDayHoliday :arg="arg" :events="events" />
        </template>

        <template #day-cell-content-capacity="{ arg, events }">
            <CellDayCapacity :arg="arg" :events="events" allowSelect  @daySelected="selectedDayEvent = $event"/>
        </template>
    </Calendar>
</template>

<style lang="scss" scoped>


</style>