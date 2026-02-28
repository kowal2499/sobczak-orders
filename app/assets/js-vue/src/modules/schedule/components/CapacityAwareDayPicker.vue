<template>
    <div>
        <div class="calendar-header">
            <button class="calendar-arrow" @click="prevMonth">&#8592;</button>
            <span class="calendar-title">{{ monthName }} {{ year }}</span>
            <button class="calendar-arrow" @click="nextMonth">&#8594;</button>
        </div>
        <div class="calendar">
            <div class="calendar-row" v-for="(week, weekIdx) in monthDays" :key="weekIdx">
                <div
                    class="calendar-day"
                    v-for="(day, dayIdx) in week"
                    :key="dayIdx"
                    :class="{
                        'empty': day.day === 0,
                        'calendar-day--selected': day.dateString === value,
                        'calendar-day--holiday': events.holidays[day.dateString],
                    }"
                    @click="onSelectDay(day)"
                    v-if="day.day !== 0"
                >
                    <CapacityProgress
                        v-if="events.capacity[day.dateString]"
                        :capacity="events.capacity[day.dateString].capacity"
                        :capacityBurned="events.capacity[day.dateString].capacityBurned"
                    />
                    <div class="font-weight-bold" style="font-size: .975rem">{{ day.day }}</div>
                </div>
                <div
                    v-else
                    class="calendar-day empty"
                    :key="'empty-' + dayIdx"
                ></div>
            </div>
        </div>
    </div>
</template>

<script>
import Calendar from 'calendar'
import { getLocalDate } from '@/helpers'
import CapacityProgress from './CapacityProgress.vue'
import { fetchCapatity, fetchHolidays } from '@/modules/schedule/repository/scheduleRepository'
const MONTH_NAMES = [
    'styczeń', 'luty', 'marzec', 'kwiecień', 'maj', 'czerwiec',
    'lipiec', 'sierpień', 'wrzesień', 'październik', 'listopad', 'grudzień'
];

export default {
    name: "CapacityAwareDayPicker",
    props: {
        incomingFactorValue: {
            type: Number,
            default: 0
        },
        value: String
    },

    components: { CapacityProgress },

    created() {
        const now = new Date();
        this.year = now.getFullYear()
        this.month = now.getMonth() + 1
    },

    watch: {
        range: {
            handler() {
                Promise.all([
                    this.fetchHolidayEvents(this.range),
                    this.fetchCapacityEvents(this.range),
                ]).then(([holidayEvents, capacityEvents]) => {
                    this.events.holidays = holidayEvents
                    this.events.capacity = capacityEvents
                })
            },
            deep: true,
        },
    },

    computed: {
        monthDays() {
            const cal = new Calendar.Calendar(1);
            const raw = cal.monthDays(this.year, this.month - 1);
            return raw.map((week) =>
                week.map((day) => {
                    if (day === 0) {
                        return { day: 0, dateString: '' };
                    }
                    const mm = String(this.month).padStart(2, '0');
                    const dd = String(day).padStart(2, '0');
                    return {
                        day,
                        dateString: `${this.year}-${mm}-${dd}`
                    };
                })
            );
        },
        monthName() {
            return MONTH_NAMES[this.month - 1];
        },
        range() {
            const start = getLocalDate(new Date(this.year, this.month - 1, 1))
            const end = getLocalDate(new Date(this.year, this.month, 0))
            return { start, end };
        },
    },

    methods: {
        prevMonth() {
            if (this.month === 1) {
                this.month = 12;
                this.year--;
            } else {
                this.month--;
            }
        },
        nextMonth() {
            if (this.month === 12) {
                this.month = 1;
                this.year++;
            } else {
                this.month++;
            }
        },

        async fetchHolidayEvents({ start, end }) {
            const {data} = await fetchHolidays(start, end)
            const holidaysMap = {}
            data.forEach(event => {
                holidaysMap[event.date] = event.description
            })
            return holidaysMap
        },
        async fetchCapacityEvents({ start, end }) {
            const {data} = await fetchCapatity(start, end)
            const capacityMap = {}
            data.forEach(event => {
                capacityMap[event.date] = {
                    capacity: event.capacity,
                    capacityBurned: event.capacityBurned,
                }
            })
            return capacityMap
        },
        formatDate(year, month, day) {
            // Zwraca datę w formacie YYYY-MM-DD
            if (!day) return '';
            const mm = String(month).padStart(2, '0');
            const dd = String(day).padStart(2, '0');
            return `${year}-${mm}-${dd}`;
        },

        onSelectDay({ day, dateString }) {
            if (day === 0 || this.events.holidays[dateString]) {
                return
            }
            this.$emit('input', dateString)

            if (!dateString) {
                return 0
            }
            const capacityEvent = this.events.capacity[dateString]
            if (!capacityEvent) {
                return 0
            }

            console.log(                Math.max((capacityEvent.capacityBurned + this.incomingFactorValue/100) - capacityEvent.capacity, 0)
            )

            this.$emit(
                'capacityExceeded',
                Math.max((capacityEvent.capacityBurned + this.incomingFactorValue/100) - capacityEvent.capacity, 0)
            )
        }
    },

    data: () => ({
        events: {
            holidays: {},
            capacity: [],
        },
        year: null,
        month: null,
        selectedDay: null,
    })
}
</script>

<style scoped lang="scss">
.calendar-header {
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 8px;
    gap: 16px;
}
.calendar-title {
    font-weight: bold;
    font-size: 18px;
    min-width: 120px;
    text-align: center;
}
.calendar-arrow {
    background: none;
    border: none;
    font-size: 20px;
    cursor: pointer;
    padding: 4px 8px;
    transition: background 0.2s;
}
.calendar-arrow:hover {
    background: #eee;
}
.calendar {
    display: flex;
    flex-direction: column;
    gap: 2px;
    align-items: center;
}
.calendar-row {
    display: flex;
    flex-direction: row;
    gap: 2px;
}
.calendar-day {
    width: 100px;
    height: 50px;
    padding: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    gap: 0.5rem;
    background: #f5f5f5;
    border: 1px solid #ddd;
    font-size: 14px;
    box-sizing: border-box;
    transition: background 0.2s;
    &:not(.empty):not(&--holiday) {
        cursor: pointer;
    }
    &:hover:not(.empty):not(&--holiday):not(&--selected) {
        background: #e0e0e0;
    }

    &--selected {
        background: var(--colorPrimary);
        color: var(--colorWhite100);
        .text-muted {
            color: var(--colorWhite100) !important;
        }
    }

    &--holiday {
        background-color: rgba(220, 53, 69, 0.3);
        color: var(--colorWhite100);
    }
}
.calendar-day.empty {
    background: transparent;
    border: none;
}
</style>