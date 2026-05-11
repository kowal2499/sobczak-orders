<script>
import MetricLayout from "../MetricLayout.vue"
import WeekCapacityCard from "../../../../schedule/components/WeekCapacityCard.vue"
import { getWeeklyCapacity } from "../../../repository"

function getMondayKey(dateStr) {
    const d = new Date(dateStr)
    const diff = d.getDay() === 0 ? -6 : 1 - d.getDay()
    const mon = new Date(d)
    mon.setDate(d.getDate() + diff)
    return mon.toISOString().split('T')[0]
}

function aggregateByWeek(dailyData) {
    if (!dailyData || !Array.isArray(dailyData)) {
        return []
    }
    const map = {}
    dailyData.forEach(day => {
        const key = getMondayKey(day.date)
        if (!map[key]) {
            map[key] = { days: [], firstWorking: null }
        }
        map[key].days.push(day)
        if (!map[key].firstWorking && day.capacity > 0) {
            map[key].firstWorking = day
        }
    })
    return Object.entries(map)
        .sort(([a], [b]) => a.localeCompare(b))
        .map(([, week]) => ({
            dateStart: week.days[0].date,
            dateEnd: week.days[week.days.length - 1].date,
            workingDays: week.days.filter(d => d.capacity > 0).length,
            capacity: week.firstWorking ? week.firstWorking.capacity : 0,
            capacityBurned: week.firstWorking ? week.firstWorking.capacityBurned : 0,
            agreementLines: week.firstWorking ? week.firstWorking.agreementLines : [],
        }))
}

export default {
    name: "WeeklyCapacityMetric",
    components: { MetricLayout, WeekCapacityCard },
    props: {
        data: {
            type: Array,
            default: null
        },
        isBusy: {
            type: Boolean,
            default: false
        },
        dateStart: { type: String, default: null },
        dateEnd: { type: String, default: null },
    },
    computed: {
        baseWeeks() {
            return aggregateByWeek(this.data)
        },
        forecastWeeks() {
            return aggregateByWeek(this.forecastData)
        },
        weeks() {
            if (!this.showForecast || !Array.isArray(this.forecastData)) {
                return this.baseWeeks.map(w => ({ ...w, capacityBurnedForecast: 0 }))
            }
            const forecastByKey = {}
            this.forecastWeeks.forEach(w => { forecastByKey[w.dateStart] = w })
            return this.baseWeeks.map(bw => {
                const fw = forecastByKey[bw.dateStart]
                if (!fw) {
                    return { ...bw, capacityBurnedForecast: 0 }
                }
                return {
                    ...bw,
                    capacityBurnedForecast: Math.max(0, fw.capacityBurned - bw.capacityBurned),
                    agreementLines: fw.agreementLines,
                }
            })
        },
    },
    watch: {
        showForecast(value) {
            if (value) {
                this.fetchForecast()
            } else {
                this.forecastData = null
            }
        },
        dateStart() { if (this.showForecast) this.fetchForecast() },
        dateEnd() { if (this.showForecast) this.fetchForecast() },
    },
    methods: {
        toggleForecast() {
            this.showForecast = !this.showForecast
        },
        async fetchForecast() {
            if (!this.dateStart || !this.dateEnd) {
                return
            }
            this.forecastBusy = true
            try {
                const { data } = await getWeeklyCapacity(this.dateStart, this.dateEnd, { includeGhost: true })
                this.forecastData = Array.isArray(data) ? data : []
            } finally {
                this.forecastBusy = false
            }
        },
    },
    data: () => ({
        showForecast: false,
        forecastBusy: false,
        forecastData: null,
    }),
}
</script>

<template>
    <MetricLayout :is-busy="isBusy || forecastBusy" class="border-left-warning">
        <template #title>
            <div class="d-flex align-items-center justify-content-between flex-wrap">
                <span>{{ $t('dashboard.weeklyCapacityMetric') }}</span>
            </div>
        </template>

        <template #description>
            <p v-html="$t('dashboard.descriptions.weeklyCapacity.p1')"></p>
            <p v-html="$t('dashboard.descriptions.weeklyCapacity.p2')"></p>
            <p v-html="$t('dashboard.descriptions.weeklyCapacity.p3')"></p>
        </template>

        <div v-if="weeks.length" class="mt-2">
            <WeekCapacityCard
                v-for="(week, idx) in weeks"
                :key="idx"
                :week-data="week"
            />
            <b-form-checkbox :checked="showForecast" @change="toggleForecast" switch size="sm">
                {{ $t('dashboard.forecastLabel') }}
            </b-form-checkbox>
        </div>
        <div v-else-if="!isBusy" class="text-muted" style="font-size: 0.85rem">
            —
        </div>
    </MetricLayout>
</template>
