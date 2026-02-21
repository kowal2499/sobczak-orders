<script>
import FullCalendar from '@fullcalendar/vue'
import dayGridPlugin from '@fullcalendar/daygrid'
import interactionPlugin from '@fullcalendar/interaction'
import plLocale from '@fullcalendar/core/locales/pl'
import enLocale from '@fullcalendar/core/locales/en-gb'

export default {
    name: "Calendar",

    components: {
        FullCalendar,
    },

    props: {
        eventsProvider: {
            type: Function,
            default: null,
        },
    },

    mounted() {
        this.calendarApi = this.$refs.fullCalendar.getApi()
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
                plugins: [dayGridPlugin, interactionPlugin],
                locales: [plLocale, enLocale],
                locale: this.locale,
                selectable: !this.isBusy,
                editable: !this.isBusy,
                unselectAuto: false,
                events: this.events,
            }
        },

        calendarEventHandlers() {
            return {
                dateClick: () => {},
                eventClick: () => {},
                eventDrop: () => {},
                eventResize: () => {},
                select: this.onDateSelect,
                datesSet: this.onDatesSet,
            }
        }
    },

    methods: {
        onDatesSet(info) {
            if (this.isBusy) {
                return
            }

            const newStart = this.formatDateLocal(info.start)
            const newEnd = this.formatDateLocal(info.end)

            if (this.currentRange.start === newStart && this.currentRange.end === newEnd) {
                return
            }

            this.currentRange = {
                start: newStart,
                end: newEnd,
            }

            this.fetchEvents()
        },

        onDateSelect(info) {
            const selectedDates = []
            const start = new Date(info.start)
            const end = new Date(info.end)
            for (let d = start; d < end; d.setDate(d.getDate() + 1)) {
                selectedDates.push(new Date(d))
            }

            this.$emit('date-selected', selectedDates)
        },

        unselect() {
            if (this.calendarApi) {
                this.calendarApi.unselect()
            }
        },

        formatDateLocal(date) {
            const year = date.getFullYear()
            const month = String(date.getMonth() + 1).padStart(2, '0')
            const day = String(date.getDate()).padStart(2, '0')
            return `${year}-${month}-${day}`
        },

        fetchEvents() {
            if (!this.currentRange.start || !this.currentRange.end) {
                return
            }

            if (typeof this.eventsProvider !== 'function') {
                return
            }

            this.isBusy = true
            return this.eventsProvider(this.currentRange.start, this.currentRange.end)
                .then((events) => {
                    this.events = events
                })
                .finally(() => {
                    this.isBusy = false
                })
        },
    },

    data: () => ({
        calendarApi: null,
        isBusy: false,
        events: [],
        currentRange: {
            start: null,
            end: null,
        },
    })
}
</script>

<template>
    <b-overlay :show="isBusy" rounded="sm">
        <FullCalendar
            ref="fullCalendar"
            :options="{...calendarOptions, ...calendarEventHandlers}"
        />
    </b-overlay>
</template>

<style scoped lang="scss">

</style>