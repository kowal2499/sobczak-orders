<script>
import FullCalendar from '@fullcalendar/vue'
import dayGridPlugin from '@fullcalendar/daygrid'
import plLocale from '@fullcalendar/core/locales/pl'
import enLocale from '@fullcalendar/core/locales/en-gb'
import { fetchHolidayEvents } from '../../repository/workRepository'

export default {
    name: "WorkScheduleCalendar",
    components: {
        FullCalendar
    },

    methods: {
        eventsProvider(info, successCallback, failureCallback) {
            const { startStr, endStr } = info

            return fetchHolidayEvents(
                startStr.split('T').shift(),
                endStr.split('T').shift()
            ).then(({data}) => {
                successCallback(data.map(event => {
                    return {
                        title: event.description,
                        start: (new Date(`${event.date}T23:59:59`)),
                        end: (new Date(`${event.date}T00:00:00`)),
                        allDay: true,
                        display: 'background',
                    }
                }))
            })
        }
    },

    computed: {
        locale() {
            return {
                gb: 'en-gb',
                pl: 'pl',
            }[this.$user.user.locale] || 'en-gb'
        },

        calendarOptions() {
            return {
                initialView: 'dayGridMonth',
                weekends: true,
                plugins: [dayGridPlugin],
                locales: [plLocale, enLocale],
                locale: this.locale,
                events: this.eventsProvider,
            }
        }
    }
}
</script>

<template>
    <div>
        <FullCalendar :options='calendarOptions'>
<!--            <template #eventContent="{ event }">-->
<!--                <div class="event-content">-->
<!--                    {{ event.title }}-->
<!--                </div>-->
<!--            </template>-->
        </FullCalendar>
    </div>
</template>

<style scoped lang="scss">

</style>