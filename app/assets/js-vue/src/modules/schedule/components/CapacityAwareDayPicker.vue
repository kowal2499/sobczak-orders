<template>
    <div>
        <div class="calendar-header">
            <span class="calendar-title">{{ monthName }} {{ year }}</span>
            <button  class="btn btn-outline-primary"
                     @click="prevMonth" :disabled="!canPrev">
                <font-awesome-icon icon="chevron-left" />
            </button>
            <button class="btn btn-outline-primary" @click="nextMonth">
                <font-awesome-icon icon="chevron-right" />
            </button>
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
                        'calendar-day--unavailable': !selectableDays.includes(day.dateString),
                        'calendar-day--exceeded': !strictMode && noCapacityDays.includes(day.dateString)
                    }"
                    v-if="day.day !== 0"
                >
                    <CapacityProgress
                        v-if="false && events.capacity[day.dateString]"
                        :capacity="events.capacity[day.dateString].capacity"
                        :capacityBurned="realBurnedCapacity[day.dateString]"
                    />
                    <div class="day-number">{{ day.day }}</div>
                    <div class="d-flex flex-column justify-content-center h-100">
                        <font-awesome-icon class="text-white" icon="check" v-if="day.dateString === value" size="lg"/>
                        <button :class="['btn btn-sm', !strictMode && noCapacityDays.includes(day.dateString) ? 'btn-outline-success' : 'btn-outline-success']"
                                v-else-if="selectableDays.includes(day.dateString)"
                                @click="onSelectDay(day)"
                        >
                            wybierz
                        </button>
                    </div>
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
        strictMode: {
            type: Boolean,
            default: true
        },
        value: String
    },

    components: { CapacityProgress },

    created() {
        const now = new Date();
        this.year = now.getFullYear()
        this.month = now.getMonth() + 1
        this.borderDate = new Date(now.getFullYear(), now.getMonth(), 1)
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
        isNoCapacityInSelectedDay(value) {
            if (value) {
                this.$emit('input', null)
                EventBus.$emit('message', {
                    type: 'warning',
                    content: 'Wybrany termin jest niedostępny dla tego produktu. Wybierz inny termin.'
                });
            }
        }
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
        canPrev() {
            const visibleFirstDay = this.monthDays.flat().find((day) => day.day > 0)
            if (!visibleFirstDay) {
                return false
            }
            const visibleFirstDate = new Date(visibleFirstDay.dateString)
            const normalizedVisibleDate = new Date(
                visibleFirstDate.getFullYear(),
                visibleFirstDate.getMonth(),
                1
            )
            return this.borderDate < normalizedVisibleDate
        },

        realBurnedCapacity() {
            return Object.keys(this.events.capacity)
                .reduce((acc, key) => {
                    acc[key] = this.events.capacity[key].capacityBurned + (this.incomingFactorValue / 100)
                    return acc
                }, {})
        },

        noCapacityDays() {
            return Object.keys(this.events.capacity).filter(date => {
                return this.realBurnedCapacity[date] >= this.events.capacity[date].capacity;
            })
        },

        frozenPeriodDates() {
            const frozenWeeksCount = 3;
            const today = new Date();

            // Znajdź pierwszy poniedziałek po zamrożonym okresie (za frozenWeeksCount tygodni)
            const daysToAdd = frozenWeeksCount * 7;
            const targetMondayDate = new Date(today);
            targetMondayDate.setDate(today.getDate() + (daysToAdd - today.getDay() + 1));

            // Jeśli dziś jest poniedziałek, to docelowy poniedziałek to za dokładnie frozenWeeksCount * 7 dni
            if (today.getDay() === 1) {
                targetMondayDate.setDate(today.getDate() + daysToAdd);
            }

            // Użyj funkcji pomocniczej do wygenerowania dat
            return this.generateDateStrings(today, targetMondayDate);
        },

        pastDaysInCurrentView() {
            const today = new Date();
            // Pierwszy dzień aktualnie widocznego miesiąca
            const firstDayOfMonth = new Date(this.year, this.month - 1, 1);

            // Użyj funkcji pomocniczej do wygenerowania dat
            return this.generateDateStrings(firstDayOfMonth, today);
        },

        selectableDays() {
            return Object.keys(this.events.capacity).filter(date => {
                if (this.strictMode) {
                    return !this.noCapacityDays.includes(date) &&
                        !this.frozenPeriodDates.includes(date) &&
                        !this.pastDaysInCurrentView.includes(date)
                } else {
                    return !this.pastDaysInCurrentView.includes(date)
                }
            })
        },

        isNoCapacityInSelectedDay() {
            return this.value && this.noCapacityDays.includes(this.value)
        }
    },

    methods: {
        generateDateStrings(startDate, endDate) {
            const dates = [];
            const currentDate = new Date(startDate);

            while (currentDate < endDate) {
                const dateString = currentDate.getFullYear() + '-' +
                    String(currentDate.getMonth() + 1).padStart(2, '0') + '-' +
                    String(currentDate.getDate()).padStart(2, '0');
                dates.push(dateString);
                currentDate.setDate(currentDate.getDate() + 1);
            }

            return dates;
        },

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
            if (day === 0 || !this.selectableDays.includes(dateString)) {
                return
            }
            this.$emit('input', dateString)
        }
    },

    data: () => ({
        events: {
            holidays: {},
            capacity: {},
        },
        year: null,
        month: null,
        selectedDay: null,
        borderDate: null,
    })
}
</script>

<style scoped lang="scss">
.calendar-header {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 16px;
}
.calendar-title {
    color: var(--colorPrimary);
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
    margin: 2rem 0;
}
.calendar-row {
    display: flex;
    flex-direction: row;
    gap: 2px;
}
.calendar-day {
    width: 100px;
    height: 80px;
    padding: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-direction: column;
    gap: 0.5rem;
    background: rgba(var(--colorSuccessRgb), 0.2);
    border: 1px solid rgba(var(--colorSuccessRgb), 0.5);
    font-size: 14px;
    box-sizing: border-box;
    transition: all 0.25s;

    .day-number {
        width: 28px;
        height: 28px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: rgba(var(--colorSuccessRgb), 1);
        color: var(--colorWhite);
        font-size: 15px;
        font-weight: bold;
    }


    &--selected {
        border-color: var(--colorPrimary);
        background-color: var(--colorPrimary);
        color: var(--colorWhite);

        .day-number {
            color: var(--colorPrimary);
            background-color: var(--colorWhite);
        }
    }
    &--exceeded {
        background: rgba(var(--colorYellowRgb), 0.2);
        border: var(--colorYellow);
        .day-number {
            background-color: var(--colorYellow);
        }
    }
    &--holiday, &--unavailable {
        cursor: not-allowed;
        opacity: 0.5;
        background-color: rgba(220, 53, 69, 0.4);
        border: 1px solid rgba(220, 53, 69, 0.8);
        .day-number {
            background-color: rgba(220, 53, 69, 0.8);
        }
    }

}
.calendar-day.empty {
    background: transparent;
    border: none;
}
</style>