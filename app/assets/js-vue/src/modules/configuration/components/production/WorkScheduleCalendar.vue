<script>
import Calendar from '@/components/base/Calendar.vue'
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
        Calendar,
        WorkScheduleForm,
        ModalAction,
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

    methods: {
        eventsProvider(start, end) {
            return fetchHolidays(start, end).then(({data}) => {
                return data.map(event => ({
                    identifier: event.id,
                    dayType: event.dayType,
                    title: event.description,
                    start: (new Date(`${event.date}T23:59:59`)),
                    end: (new Date(`${event.date}T00:00:00`)),
                    allDay: true,
                    display: 'background',
                }))
            })
        },

        onDateSelected(selectedDates) {
            this.selectedDates = selectedDates

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
                calendar.fetchEvents()
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
        form: resetForm()
    })
}
</script>

<template>
    <div>
        <Calendar
            ref="calendar"
            :events-provider="eventsProvider"
            @date-selected="onDateSelected"
        />

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