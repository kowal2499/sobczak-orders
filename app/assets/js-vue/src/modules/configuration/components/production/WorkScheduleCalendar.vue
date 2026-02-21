<script>
import FullCalendar from '@fullcalendar/vue'
import dayGridPlugin from '@fullcalendar/daygrid'
import interactionPlugin from '@fullcalendar/interaction'
import plLocale from '@fullcalendar/core/locales/pl'
import enLocale from '@fullcalendar/core/locales/en-gb'
import { fetchHolidays, fetchCapatity } from '@/modules/schedule/repository/scheduleRepository'
import { processScheduleChanges } from '../../services/WorkScheduleService'
import WorkScheduleForm from './WorkScheduleForm.vue'
import ModalAction from '@/components/base/ModalAction'

function resetForm() {
    return {
        dayType: '',
        description: '',
    }
}

export default {
    name: "WorkScheduleCalendar",
    components: {
        FullCalendar,
        WorkScheduleForm,
        ModalAction,
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

        showModal() {
            return this.selectedDates.length > 0
        },

        modalTitle() {
            if (this.selectedDates.length === 0) {
                return this.$t('config.production.scheduleFormTitle')
            }
            if (this.selectedDates.length === 1) {
                return `${this.$t('config.production.scheduleFormTitle')} - ${this.formatDateLocal(this.selectedDates[0])}`
            }
            const firstDate = this.formatDateLocal(this.selectedDates[0])
            const lastDate = this.formatDateLocal(this.selectedDates[this.selectedDates.length - 1])
            return `${this.$t('config.production.scheduleFormTitleRange')} - ${firstDate} - ${lastDate}`
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
                select: this.onDateClick,
                datesSet: this.onDatesSet,
            }
        }
    },

    methods: {
        onDatesSet(info) {
            // Zapobiega wielokrotnemu pobieraniu gdy trwa ładowanie
            if (this.isBusy) {
                return
            }

            const newStart = this.formatDateLocal(info.start)
            const newEnd = this.formatDateLocal(info.end)

            // Zapobiega wielokrotnemu pobieraniu dla tego samego zakresu
            if (this.currentRange.start === newStart && this.currentRange.end === newEnd) {
                return
            }

            this.currentRange = {
                start: newStart,
                end: newEnd,
            }
            this.fetchEvents()
        },

        fetchEvents() {
            if (!this.currentRange.start || !this.currentRange.end) {
                return
            }

            this.isBusy = true

            fetchCapatity(this.currentRange.start, this.currentRange.end).then(({data}) => {
                console.log(data)
            })

            return fetchHolidays(
                this.currentRange.start,
                this.currentRange.end
            ).then(({data}) => {
                this.events = data.map(event => {
                    return {
                        identifier: event.id,
                        dayType: event.dayType,
                        title: event.description,
                        start: (new Date(`${event.date}T23:59:59`)),
                        end: (new Date(`${event.date}T00:00:00`)),
                        allDay: true,
                        display: 'background',
                    }
                })
            }).finally(() => {
                this.isBusy = false
            })
        },

        onDateClick(info) {
            this.selectedDates = []
            const start = new Date(info.start)
            const end = new Date(info.end)
            for (let d = start; d < end; d.setDate(d.getDate() + 1)) {
                this.selectedDates.push(new Date(d))
            }

            // Ustaw dayType w formularzu jeśli wybrano dokładnie 1 dzień
            if (this.selectedDates.length === 1) {
                const dateStr = this.formatDateLocal(this.selectedDates[0])
                const existingEvent = this.events.find(event => {
                    return this.formatDateLocal(event.start) === dateStr
                })
                if (existingEvent) {
                    this.form.dayType = 'holiday'
                    this.form.description = existingEvent.title || ''
                } else {
                    this.form.dayType = 'working'
                }
            }
        },

        formatDateLocal(date) {
            const year = date.getFullYear()
            const month = String(date.getMonth() + 1).padStart(2, '0')
            const day = String(date.getDate()).padStart(2, '0')
            return `${year}-${month}-${day}`
        },

        async onSave() {
            if (this.isBusy) {
                return
            }

            const isValid = await this.$refs.formObserver.validate()
            if (!isValid) {
                return
            }

            const payload = this.selectedDates.map(date => {
                return {
                    date: this.formatDateLocal(date),
                    dayType: this.form.dayType,
                    description: this.form.description,
                }
            })
            this.isBusy = true
            return processScheduleChanges(payload, this.events).then(() => {
                this.onCloseModal()
                this.fetchEvents()
            }).finally(() => {
                this.isBusy = false
            })
        },

        onCloseModal() {
            this.selectedDates = []
            this.unselectDates()
            this.form = resetForm()
        },

        unselectDates() {
            if (this.calendarApi) {
                this.calendarApi.unselect()
            }
        },
    },

    data: () => ({
        selectedDates: [],
        calendarApi: null,
        isBusy: false,
        events: [],
        currentRange: {
            start: null,
            end: null,
        },
        form: resetForm()
    })
}
</script>

<template>
    <div>
        <b-overlay :show="isBusy" rounded="sm">
            <FullCalendar
                ref="fullCalendar"
                :options="{
                    ...calendarOptions,
                    ...calendarEventHandlers,
                }"
            />
        </b-overlay>

        <ModalAction
            :value="showModal"
            @close="onCloseModal"
            :title="modalTitle"
            :configuration="{
                hideFooter: false,
                size: 'md',
            }"
        >
            <template #default>
                <ValidationObserver ref="formObserver" v-slot="{ invalid }">
                    <WorkScheduleForm v-model="form" :is-busy="isBusy" />
                </ValidationObserver>
            </template>

            <template #modal-footer="{ close }">
                <div class="d-flex justify-content-end">
                    <button class="btn btn-secondary" @click="close">{{ $t('cancel') }}</button>
                    <button class="btn btn-primary ml-2" @click="onSave(close)">
                        <font-awesome-icon icon="save" class="mr-2" /> {{ $t('_save') }}
                    </button>
                </div>
            </template>
        </ModalAction>
    </div>
</template>

<style lang="scss">
.fc {
    --fc-bg-event-color: #ff5d6b;
    --fc-bg-event-opacity: 0.7;
    --fc-button-bg-color: #4e73df;
    --fc-button-border-color: #4e73df;
    --fc-button-hover-bg-color: #2e59d9;
    --fc-button-hover-border-color: #2e59d9;

    .fc-day {
        cursor: pointer;
    }
    .fc-bg-event .fc-event-title {
        color: #333;
    }
    .fc-daygrid-day-number {
        color: #0A0A0A;
    }

    .fc-toolbar-title {
        font-size: 1.25rem;
    }
}
</style>