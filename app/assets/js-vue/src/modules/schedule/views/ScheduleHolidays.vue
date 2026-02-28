<script>
import Calendar from '@/components/base/Calendar.vue'
import { fetchHolidays } from '../repository/scheduleRepository'
import { processScheduleChanges } from '../services/WorkScheduleService'
import ScheduleHolidaysForm from '../forms/ScheduleHolidaysForm.vue'
import ModalAction from '@/components/base/ModalAction'
import CellDayHoliday from '../components/CellDayHoliday.vue'

import {v4 as uuidv4} from 'uuid';

function resetForm() {
    return {
        dayType: '',
        description: '',
    }
}

export default {
    name: "ScheduleHolidays",
    components: {
        Calendar,
        ScheduleHolidaysForm,
        ModalAction,
        CellDayHoliday,
    },

    computed: {
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
    },

    watch: {
        'filters.date': {
            handler() {
                this.fetchEvents()
            },
            deep: true,
        }
    },

    methods: {
        fetchEvents() {
            if (!this.filters.date.start || !this.filters.date.end) {
                return
            }
            this.fetchHolidayEvents(this.filters.date)
                .then(holidayEvents => {
                    this.events.holiday = holidayEvents
                })
        },

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

        onDateSelected(data) {
            const date = data.arg.date
            this.selectedDates = [date]

            if (this.selectedDates.length === 1) {
                const dateStr = this.formatDateLocal(this.selectedDates[0])
                const existingEvent = this.$refs.calendar.events.find(event => {
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

            const calendar = this.$refs.calendar
            const payload = this.selectedDates.map(date => {
                return {
                    date: this.formatDateLocal(date),
                    dayType: this.form.dayType,
                    description: this.form.description,
                }
            })
            this.isBusy = true
            return processScheduleChanges(payload, calendar.events).then(() => {
                this.onCloseModal()
                this.fetchEvents()
            }).finally(() => {
                this.isBusy = false
            })
        },

        onCloseModal() {
            this.selectedDates = []
            this.$refs.calendar.unselect()
            this.form = resetForm()
        },
    },

    data: () => ({
        selectedDates: [],
        isBusy: false,
        form: resetForm(),

        filters: {
            date: {
                start: null,
                end: null,
            }
        },

        events: {
            holiday: []
        }
    })
}
</script>

<template>
    <div>
        <Calendar
            ref="calendar"
            :events="events.holiday"
            @date-set="onDateSet"
            :options="{ weekNumbers: true, weekNumberFormat: { week: 'numeric'} }"
        >
            <template #day-cell-content-holiday="{ arg, events }">
                <CellDayHoliday :arg="arg" :events="events" />
            </template>
            <template #day-cell-dropdown="{ arg, events }">
                <b-dropdown-item-button @click="onDateSelected({ arg, events })">Rodzaj dnia</b-dropdown-item-button>
            </template>
        </Calendar>

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
                    <ScheduleHolidaysForm v-model="form" :is-busy="isBusy" />
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
</style>