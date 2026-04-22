<script>
import MetricLayout from "../MetricLayout.vue"
import WeekCapacityCard from "../../../../schedule/components/WeekCapacityCard.vue"

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
        }
    },
    computed: {
        weeks() {
            return aggregateByWeek(this.data)
        }
    }
}
</script>

<template>
    <MetricLayout :is-busy="isBusy" class="border-left-warning">
        <template #title>{{ $t('dashboard.weeklyCapacityMetric') }}</template>
        <div v-if="weeks.length">
            <WeekCapacityCard
                v-for="(week, idx) in weeks"
                :key="idx"
                :week-data="week"
            />
        </div>
        <div v-else-if="!isBusy" class="text-muted" style="font-size: 0.85rem">
            —
        </div>
    </MetricLayout>
</template>
