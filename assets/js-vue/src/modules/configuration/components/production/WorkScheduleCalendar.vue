<script>
import FullCalendar from '@fullcalendar/vue'
import dayGridPlugin from '@fullcalendar/daygrid'
import interactionPlugin from '@fullcalendar/interaction'
import plLocale from '@fullcalendar/core/locales/pl'
import enLocale from '@fullcalendar/core/locales/en-gb'
import { fetchHolidayEvents } from '../../repository/workRepository'
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

        calendarOptions() {
            return {
                initialView: 'dayGridMonth',
                weekends: true,
                plugins: [dayGridPlugin, interactionPlugin],
                locales: [plLocale, enLocale],
                locale: this.locale,
                selectable: true,
                unselectAuto: false,
                events: this.eventsProvider,
            }
        },

        calendarEventHandlers() {
            return {
                dateClick: () => {},
                eventClick: () => {},
                eventDrop: () => {},
                eventResize: () => {},
                select: this.onDateClick,
            }
        }
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
        },

        onDateClick(info) {
            this.selectedDates = []
            const start = new Date(info.start)
            const end = new Date(info.end)
            for (let d = start; d < end; d.setDate(d.getDate() + 1)) {
                this.selectedDates.push(new Date(d))
            }
            console.log(this.selectedDates)
        },

        onSave() {
            console.log('saving form', this.form)
            this.onCloseModal()
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
        }
    },

    data: () => ({
        selectedDates: [],
        calendarApi: null,
        form: resetForm()
    })
}
</script>

<template>
    <div>
        <FullCalendar
            ref="fullCalendar"
            :options="{
                ...calendarOptions,
                ...calendarEventHandlers,
            }"
        />

        <ModalAction
            :value="showModal"
            @close="onCloseModal"
            title="Ustawienia dnia"
            :configuration="{
                hideFooter: false,
                size: 'md',
            }"
        >
            <template #default>
                <WorkScheduleForm v-model="form" />
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

<style scoped lang="scss">

</style>