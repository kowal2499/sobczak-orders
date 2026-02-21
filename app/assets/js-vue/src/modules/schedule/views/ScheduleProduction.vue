<script>
import Calendar from '@/components/base/Calendar.vue'
import { fetchCapatity } from '@/modules/schedule/repository/scheduleRepository'

export default {
    name: "ScheduleProduction",
    components: { Calendar },
    methods: {
        onDateSelected(dates) {
            console.log('onDateSelected', dates)
        },
        eventsProvider(start, end) {
            return fetchCapatity(start, end).then(({data}) => {
                console.log(data)
                return data.map(event => ({
                    start: (new Date(`${event.date}T23:59:59`)),
                    end: (new Date(`${event.date}T00:00:00`)),
                    capacity: event.capacity,
                    capacityBurned: event.capacityBurned,
                    allDay: true,
                    display: 'background',
                }))
            })
        }
    }}
</script>

<template>
    <Calendar
        @date-selected="onDateSelected"
        :eventsProvider="eventsProvider"
    />
</template>

<style scoped lang="scss">

</style>