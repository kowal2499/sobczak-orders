<script>
import Calendar from '@/components/base/Calendar.vue'
import { fetchCapatity } from '@/modules/schedule/repository/scheduleRepository'

export default {
    name: "ScheduleProduction",
    components: { Calendar },

    data: () => ({
        capacityMap: {},
    }),

    methods: {
        onSelectDay(dateStr) {
            const entry = this.capacityMap[dateStr]
            if (!entry || !this.hasAvailableCapacity(entry)) {
                return
            }
            console.log('onSelectDay', dateStr, entry)
            this.$emit('day-selected', dateStr, entry)
        },

        hasAvailableCapacity(entry) {
            return entry.capacity > 0 && entry.capacityBurned < entry.capacity
        },

        getUsagePercent(entry) {
            if (!entry || !entry.capacity || entry.capacity <= 0) {
                return 0
            }
            return Math.min(Math.round((entry.capacityBurned / entry.capacity) * 100), 100)
        },

        getCapacityColor(percent) {
            if (percent <= 25) return '#28a745'
            if (percent <= 50) return '#5cb85c'
            if (percent <= 75) return '#ffc107'
            if (percent <= 99) return '#fd7e14'
            return '#dc3545'
        },

        getBackgroundColor(percent) {
            if (percent <= 25) return 'rgba(40, 167, 69, 0.10)'
            if (percent <= 50) return 'rgba(92, 184, 92, 0.12)'
            if (percent <= 75) return 'rgba(255, 193, 7, 0.15)'
            if (percent <= 99) return 'rgba(253, 126, 20, 0.15)'
            return 'rgba(220, 53, 69, 0.15)'
        },

        formatDateLocal(date) {
            const year = date.getFullYear()
            const month = String(date.getMonth() + 1).padStart(2, '0')
            const day = String(date.getDate()).padStart(2, '0')
            return `${year}-${month}-${day}`
        },

        getEntryForDate(date) {
            const dateStr = this.formatDateLocal(date)
            return this.capacityMap[dateStr] || null
        },

        eventsProvider(start, end) {
            return fetchCapatity(start, end).then(({data}) => {
                const map = {}
                data.forEach(item => {
                    map[item.date] = {
                        capacity: item.capacity,
                        capacityBurned: item.capacityBurned,
                    }
                })
                this.capacityMap = map

                return []
            })
        },

        dayCellDidMount(info) {
            const dateStr = this.formatDateLocal(info.date)
            const entry = this.capacityMap[dateStr]

            if (!entry || !entry.capacity) {
                return
            }

            const percent = this.getUsagePercent(entry)
            info.el.style.backgroundColor = this.getBackgroundColor(percent)
        },

        onEventsLoaded() {
            this.$nextTick(() => {
                this.applyBackgroundColors()
            })
        },

        applyBackgroundColors() {
            const calendarEl = this.$refs.calendar?.$refs?.fullCalendar?.$el
            if (!calendarEl) {
                return
            }

            calendarEl.querySelectorAll('.fc-daygrid-day').forEach(cell => {
                const dateStr = cell.getAttribute('data-date')
                const entry = dateStr ? this.capacityMap[dateStr] : null

                if (entry && entry.capacity) {
                    const percent = this.getUsagePercent(entry)
                    cell.style.backgroundColor = this.getBackgroundColor(percent)
                } else {
                    cell.style.backgroundColor = ''
                }
            })
        },
    },
}
</script>

<template>
    <Calendar
        ref="calendar"
        :eventsProvider="eventsProvider"
        :dayCellDidMount="dayCellDidMount"
        @events-loaded="onEventsLoaded"
        :options="{ selectable: false, contentHeight: 600 }"
    >
        <template #day-cell-content="arg">
            <div class="schedule-day-cell">
                <div class="schedule-day-number">{{ arg.dayNumberText }}</div>

                <template v-if="getEntryForDate(arg.date)">
                    <div class="schedule-progress">
                        <div
                            class="schedule-progress-bar"
                            :style="{
                                width: getUsagePercent(getEntryForDate(arg.date)) + '%',
                                backgroundColor: getCapacityColor(getUsagePercent(getEntryForDate(arg.date)))
                            }"
                        />
                    </div>

                    <div class="schedule-capacity-label">
                        {{ getEntryForDate(arg.date).capacityBurned }}/{{ getEntryForDate(arg.date).capacity }}
                    </div>

                    <button
                        class="btn btn-sm schedule-select-btn"
                        :class="hasAvailableCapacity(getEntryForDate(arg.date)) ? 'btn-primary' : 'btn-secondary'"
                        :disabled="!hasAvailableCapacity(getEntryForDate(arg.date))"
                        @click.stop.prevent="onSelectDay(formatDateLocal(arg.date))"
                    >
                        Wybierz
                    </button>
                </template>
            </div>
        </template>
    </Calendar>
</template>

<style lang="scss">
.fc .fc-daygrid-day-frame {
    display: flex;
    flex-direction: column;
}

.fc .fc-daygrid-day-top {
    width: 100%;
}

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
    align-items: center;
    width: 100%;
    gap: 3px;
    padding: 2px 4px 4px;
}

.schedule-day-number {
    align-self: flex-end;
    font-size: 0.85rem;
    line-height: 1.2;
    margin-bottom: 2px;
}

.schedule-progress {
    width: 100%;
    height: 6px;
    background-color: #e9ecef;
    border-radius: 3px;
    overflow: hidden;
}

.schedule-progress-bar {
    height: 100%;
    border-radius: 3px;
    transition: width 0.3s ease;
}

.schedule-capacity-label {
    font-size: 0.7rem;
    color: #6c757d;
    line-height: 1;
}

.schedule-select-btn {
    font-size: 0.65rem;
    padding: 1px 8px;
    line-height: 1.4;
    border-radius: 3px;
}

.fc-daygrid-day-events {
    display: none !important;
}
</style>