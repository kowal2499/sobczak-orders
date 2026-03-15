<script>
import FullCalendar from '@fullcalendar/vue'
import dayGridPlugin from '@fullcalendar/daygrid'
import interactionPlugin from '@fullcalendar/interaction'
import plLocale from '@fullcalendar/core/locales/pl'
import enLocale from '@fullcalendar/core/locales/en-gb'
import { getLocalDate } from '@/helpers'

export default {
    name: "Calendar",

    components: {
        FullCalendar,
    },

    props: {
        events: {
            type: Array,
            default: () => ([]),
        },
        options: {
            type: Object,
            default: () => ({})
        },
        dayCellDidMount: {
            type: Function,
            default: null,
        },
        height: {
            type: [Number, String],
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
            const options = {
                initialView: 'dayGridMonth',
                weekends: true,
                plugins: [dayGridPlugin, interactionPlugin],
                locales: [plLocale, enLocale],
                locale: this.locale,
                selectable: !this.isBusy,
                editable: !this.isBusy,
                unselectAuto: false,
                events: this.events,
                ...this.options,
            }

            if (typeof this.dayCellDidMount === 'function') {
                options.dayCellDidMount = this.dayCellDidMount
            }

            return options
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
        },

        eventsDateTypeMap() {
            const events = {}
            for (let event of this.events) {
                if (!event.dateKey || !event.type) {
                    continue
                }
                if (!events.hasOwnProperty(event.dateKey)) {
                    events[event.dateKey] = {}
                }
                if (!events[event.dateKey].hasOwnProperty(event.type)) {
                    events[event.dateKey][event.type] = []
                }
                events[event.dateKey][event.type].push(event)
            }
            return events
        },
    },

    methods: {
        onDatesSet(info) {
            const newStart = this.getLocalDate(info.start)
            const newEnd = this.getLocalDate(info.end)

            if (this.currentRange.start === newStart && this.currentRange.end === newEnd) {
                return
            }

            this.currentRange = {
                start: newStart,
                end: newEnd,
            }

            this.$emit('date-set', { start: newStart, end: newEnd })
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

        getLocalDate: getLocalDate
    },

    data: () => ({
        calendarApi: null,
        isBusy: false,
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
        >
            <template #day-cell-content="arg">
                <div class="schedule-day-cell">
                    <div class="flex-schedule-row">
                        <div class="flex-schedule-content">
                            <template v-for="type in Object.keys(eventsDateTypeMap[getLocalDate(arg.date)] || {})">
                                <slot
                                    :name="`day-cell-content-${type}`"
                                    v-bind="{
                                        arg,
                                        events: eventsDateTypeMap[getLocalDate(arg.date)][type] || []
                                    }"
                                />
                            </template>
                        </div>
                        <div class="d-flex flex-column gap-2">
                            <div class="schedule-day-number">{{ arg.dayNumberText }}</div>
                            <b-dropdown
                                size="sm"
                                variant="outline-primary"
                                class="btn-day-cell-dropdown"
                                no-caret
                            >
                                <template #button-content>
                                    <font-awesome-icon icon="bars" />
                                </template>
                                <slot
                                    name="day-cell-dropdown"
                                    v-bind="{ arg, events: eventsDateTypeMap[getLocalDate(arg.date)] || [] }"
                                />
                            </b-dropdown>
                        </div>
                    </div>
                </div>

            </template>

<!--            <template #event-content="{ event }">-->
<!--                <div>jestem eventem {event.title}</div>-->
<!--            </template>-->
        </FullCalendar>
    </b-overlay>
</template>

<style lang="scss">
.fc .fc-daygrid-day-top .fc-daygrid-day-number {
    display: block;
    width: 100%;
    &:hover {
        text-decoration: none;
    }
}
.schedule-day-cell {
    display: flex;
    flex-direction: column;
    align-items: start;
    justify-content: space-between;
    width: 100%;
    gap: 1rem;
    padding: 2px 4px 4px;
}
.schedule-day-number {
    margin-left: auto;
    font-size: 0.75rem;
    font-weight: bold;
    width: 25px;
    height: 25px;
    border-radius: 12px;
    background-color: var(--colorPrimary);
    color: white;
    display: flex;
    justify-content: center;
    align-items: center;
    flex-shrink: 0;
}
.flex-schedule-row {
    display: flex;
    flex-direction: row;
    gap: 1rem;
    width: 100%;
    align-items: flex-start;
}
.schedule-day-cell > .flex-schedule-row > .flex-schedule-content {
    flex: 1 1 0;
    min-width: 0;
    max-width: 100%;
}
.fc .fc-bg-event {
    background: repeating-linear-gradient(
        -45deg,
        color-mix(in srgb, var(--fc-bg-event-color) 90%, white 30%) 0px,
        color-mix(in srgb, var(--fc-bg-event-color) 90%, white 30%) 20px,
        color-mix(in srgb, var(--fc-bg-event-color) 80%, white 40%) 20px,
        color-mix(in srgb, var(--fc-bg-event-color) 80%, white 40%) 40px
    ) /* 0 0 fixed */;
    opacity: var(--fc-bg-event-opacity);
}

.fc-day-other {
    //visibility: hidden;
}

.fc-daygrid-day-events {
    //display: none !important;
}
</style>