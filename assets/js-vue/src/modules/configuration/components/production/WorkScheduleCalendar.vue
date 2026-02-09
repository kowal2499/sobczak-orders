<script>
import FullCalendar from '@fullcalendar/vue'
import dayGridPlugin from '@fullcalendar/daygrid'

export default {
    name: "WorkScheduleCalendar",
    components: {
        FullCalendar
    },
    computed: {
        calendarOptions() {
            return {
                initialView: 'dayGridMonth',
                weekends: true,
                plugins: [dayGridPlugin],
                events: [
                    { title: 'event 1', date: '2026-02-09' },
                    { title: 'event 2', date: '2026-02-12' },
                    { title: 'święto', date: '2026-02-10', display: 'background', color: '#ff9f89' },
                ]
            }
        }
    }
}
</script>

<template>
    <div>
        <FullCalendar :options='calendarOptions'>
            <template #eventContent="{ event }">
                <div class="event-content">
                    *** {{ event.title }} ***
                </div>
            </template>
        </FullCalendar>
    </div>
</template>

<style scoped lang="scss">

</style>