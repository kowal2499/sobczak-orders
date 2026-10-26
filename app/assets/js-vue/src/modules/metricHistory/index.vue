<script>
import { defineComponent } from 'vue'
import { DEPARTMENTS } from '@/helpers'
import { departmentColor } from '@/services/departmentColors'
import HistoryChart from './components/HistoryChart.vue'
import { captureToday, fetchHistory } from './repository/metricHistoryRepository'
import {
    backfilledDays,
    daysInMonth,
    findSeries,
    isoWeekMonday,
    monthKey,
    monthRange,
    ratio,
    shiftMonth,
    toDaily,
    valuesByDay,
} from './services/series'

const ATTENTION_LISTS = ['overdueOrders', 'unplannedOrders', 'notStartedProductions', 'overdueProductions']
const LINE_COLORS = ['#4E79A7', '#F28E2B', '#59A14F', '#E15759', '#76B7B2', '#B07AA1', '#EDC948', '#9C755F']
const PREVIOUS_ALPHA = 0.45

export default defineComponent({
    name: 'MetricHistory',
    components: { HistoryChart },
    computed: {
        breadcrumbs() {
            return [
                { icon: 'home', href: '/', label: this.$t('metric_history.breadcrumb.home') },
                { label: this.$t('metric_history.breadcrumb.reports') },
                { label: this.$t('metric_history.breadcrumb.current') },
            ]
        },
        previousMonth() {
            return shiftMonth(this.month, -1)
        },
        labels() {
            return Array.from({ length: daysInMonth(this.month) }, (_, index) => index + 1)
        },
        backfilled() {
            return backfilledDays(this.series, this.month)
        },
        departments() {
            const present = new Set(this.series.map(s => s.dimension))
            return DEPARTMENTS.filter(department => present.has(department.slug))
        },
        departmentOptions() {
            return [
                { value: null, text: this.$t('metric_history.filters.all_departments') },
                ...this.departments.map(department => ({ value: department.slug, text: department.name })),
            ]
        },
        modeOptions() {
            return [
                { value: 'cumulative', text: this.$t('metric_history.filters.cumulative') },
                { value: 'daily', text: this.$t('metric_history.filters.daily') },
            ]
        },
        fieldOptions() {
            return [
                { value: 'factorSum', text: this.$t('metric_history.filters.factors') },
                { value: 'itemCount', text: this.$t('metric_history.filters.count') },
            ]
        },
        chartedDepartments() {
            return this.department === null
                ? this.departments
                : this.departments.filter(department => department.slug === this.department)
        },
        canCompare() {
            return this.department !== null
        },
        hasAnyData() {
            return this.series.some(s => s.points.some(point => point.date.startsWith(this.month)))
        },
        canManage() {
            return this.$user.can('metric-snapshot.manage')
        },
        computedAtLabel() {
            return this.computedAt ? new Date(this.computedAt).toLocaleString(this.$i18n.locale) : null
        },

        ordersDatasets() {
            return [
                ...this.metricDatasets('orders_finished', this.$t('metric_history.metric.orders_finished'), LINE_COLORS[2], true),
                ...this.metricDatasets('orders_pending', this.$t('metric_history.metric.orders_pending'), LINE_COLORS[0], false),
            ]
        },
        bonusDatasets() {
            return this.departmentDatasets(department => this.flowValues(
                findSeries(this.series, 'departments_bonus', { dimension: department.slug }),
                this.field
            ))
        },
        onTimeDatasets() {
            return this.departmentDatasets(department => key => ratio(
                valuesByDay(findSeries(this.series, 'on_time_in', { dimension: department.slug }), key),
                valuesByDay(findSeries(this.series, 'on_time_late', { dimension: department.slug }), key)
            ))
        },
        capacityDatasets() {
            return this.chartedDepartments.map(department => this.dataset(
                department.name,
                departmentColor(department.slug).border,
                valuesByDay(findSeries(this.series, 'capacity', { dimension: department.slug, periodKey: this.month }), this.month)
            ))
        },
        weeklyDatasets() {
            const weeks = [...new Set(findSeries(this.series, 'weekly_capacity').map(s => s.periodKey))].sort()

            return weeks
                .map((week, index) => {
                    const burned = valuesByDay(findSeries(this.series, 'weekly_capacity', { periodKey: week }), this.month)
                    const limit = valuesByDay(findSeries(this.series, 'weekly_capacity_limit', { periodKey: week }), this.month)
                    const usage = burned.map((value, day) => (value === null || !limit[day]
                        ? null
                        : Math.round(1000 * value / limit[day]) / 10))

                    return this.dataset(this.weekLabel(week), LINE_COLORS[index % LINE_COLORS.length], usage)
                })
                .filter(dataset => dataset.data.some(value => value !== null))
        },
        attentionDatasets() {
            const visible = new Set(this.series
                .filter(s => s.metric === 'attention_lists')
                .map(s => s.dimension.split(':')[0]))

            return ATTENTION_LISTS
                .filter(list => visible.has(list))
                .flatMap((list, index) => {
                    const seriesOf = () => this.series.filter(s => s.metric === 'attention_lists'
                        && s.dimension.split(':')[0] === list
                        && (this.department === null || !s.dimension.includes(':') || s.dimension.endsWith(`:${this.department}`)))

                    return this.withPrevious(
                        this.$t(`dashboard.attention.${list}.title`),
                        LINE_COLORS[index],
                        key => valuesByDay(seriesOf(), key, 'itemCount'),
                        true
                    )
                })
        },
    },
    methods: {
        async load() {
            this.loading = true
            try {
                const { from } = monthRange(this.previousMonth)
                const { to } = monthRange(this.month)
                const { data } = await fetchHistory(from, to)
                this.series = data.series
                this.computedAt = data.computedAt
            } catch {
                this.series = []
                this.$flash.danger(this.$t('metric_history.error.load'))
            } finally {
                this.loading = false
            }
        },
        async capture() {
            this.capturing = true
            try {
                await captureToday()
                this.$flash.success(this.$t('metric_history.capture_done'))
                await this.load()
            } catch (error) {
                this.$flash.danger(error?.response?.data?.error || this.$t('metric_history.error.capture'))
            } finally {
                this.capturing = false
            }
        },
        flowValues(seriesList, field) {
            return key => {
                const cumulative = valuesByDay(seriesList, key, field)
                return this.mode === 'daily' ? toDaily(cumulative) : cumulative
            }
        },
        metricDatasets(metric, label, color, isFlow) {
            const seriesList = findSeries(this.series, metric)
            const values = isFlow
                ? this.flowValues(seriesList, this.field)
                : key => valuesByDay(seriesList, key, this.field)

            return this.withPrevious(label, color, values, true)
        },
        departmentDatasets(valuesFor) {
            return this.chartedDepartments.flatMap(department => this.withPrevious(
                department.name,
                departmentColor(department.slug).border,
                valuesFor(department),
                this.canCompare
            ))
        },
        /**
         * Seria bieżącego miesiąca i - przy porównaniu - poprzedniego, nałożona po dniu miesiąca.
         */
        withPrevious(label, color, valuesFor, comparable) {
            const datasets = [this.dataset(label, color, valuesFor(this.month))]
            if (comparable && this.compare) {
                const previous = valuesFor(this.previousMonth).slice(0, this.labels.length)
                datasets.push({
                    ...this.dataset(`${label} (${this.$t('metric_history.previous_month')})`, color, previous),
                    borderDash: [5, 4],
                    borderColor: this.fade(color),
                    pointRadius: 0,
                })
            }
            return datasets
        },
        dataset(label, color, data) {
            return {
                label,
                data,
                borderColor: color,
                backgroundColor: color,
                fill: false,
                lineTension: 0,
                borderWidth: 2,
                pointRadius: data.map((_, index) => (this.backfilled.has(index) ? 3 : 2)),
                pointBackgroundColor: data.map((_, index) => (this.backfilled.has(index) ? '#fff' : color)),
            }
        },
        fade(hex) {
            const [r, g, b] = [1, 3, 5].map(offset => parseInt(hex.slice(offset, offset + 2), 16))
            return `rgba(${r}, ${g}, ${b}, ${PREVIOUS_ALPHA})`
        },
        weekLabel(week) {
            const monday = isoWeekMonday(week)
            const sunday = new Date(monday)
            sunday.setDate(monday.getDate() + 6)
            const short = date => `${String(date.getDate()).padStart(2, '0')}.${String(date.getMonth() + 1).padStart(2, '0')}`

            return `${this.$t('metric_history.week', { number: Number(week.split('-W')[1]) })} (${short(monday)}-${short(sunday)})`
        },
    },
    watch: {
        month() {
            this.load()
        },
    },
    mounted() {
        this.load()
    },
    data() {
        return {
            month: monthKey(new Date()),
            department: null,
            mode: 'cumulative',
            field: 'factorSum',
            compare: false,
            series: [],
            computedAt: null,
            loading: false,
            capturing: false,
        }
    },
})
</script>

<template>
    <div>
        <SectionBlockTitle block :title="$t('metric_history.title')" :breadcrumbs="breadcrumbs">
            <template #filters>
                <b-form inline class="metric-history__filters">
                    <b-form-input v-model="month" type="month" size="sm" class="mr-2" required />
                    <b-form-select v-model="department" :options="departmentOptions" size="sm" class="mr-2" />
                    <b-form-select v-model="mode" :options="modeOptions" size="sm" class="mr-2" />
                    <b-form-select v-model="field" :options="fieldOptions" size="sm" class="mr-2" />
                    <b-form-checkbox v-model="compare" switch class="mr-3">
                        {{ $t('metric_history.filters.compare') }}
                    </b-form-checkbox>
                    <b-spinner v-if="loading" small variant="secondary" />
                </b-form>
            </template>
        </SectionBlockTitle>

        <SectionBlock class="section-gap d-flex align-items-center justify-content-between flex-wrap">
            <span class="text-muted small">
                {{ $t('metric_history.computed_at') }}:
                <template v-if="computedAtLabel">{{ computedAtLabel }}</template>
                <template v-else>{{ $t('metric_history.never_computed') }}</template>
            </span>
            <button
                v-if="canManage"
                type="button"
                class="btn btn-primary btn-sm"
                :disabled="capturing"
                @click="capture"
            >
                <b-spinner v-if="capturing" small class="mr-1" />
                {{ $t('metric_history.action.capture') }}
            </button>
        </SectionBlock>

        <SectionBlock v-if="!loading && !hasAnyData" class="section-gap text-muted">
            {{ $t('metric_history.no_data') }}
        </SectionBlock>

        <template v-else>
            <HistoryChart
                v-if="ordersDatasets.length"
                :title="$t('metric_history.chart.orders.title')"
                :description="$t('metric_history.chart.orders.description')"
                :labels="labels"
                :datasets="ordersDatasets"
                :backfilled="backfilled"
            />
            <HistoryChart
                v-if="bonusDatasets.length"
                :title="$t('metric_history.chart.bonus.title')"
                :description="$t('metric_history.chart.bonus.description')"
                :labels="labels"
                :datasets="bonusDatasets"
                :backfilled="backfilled"
            />
            <HistoryChart
                v-if="onTimeDatasets.length"
                :title="$t('metric_history.chart.on_time.title')"
                :description="$t('metric_history.chart.on_time.description')"
                :labels="labels"
                :datasets="onTimeDatasets"
                :backfilled="backfilled"
                percent
            />
            <HistoryChart
                v-if="capacityDatasets.length"
                :title="$t('metric_history.chart.capacity.title')"
                :description="$t('metric_history.chart.capacity.description')"
                :labels="labels"
                :datasets="capacityDatasets"
            />
            <HistoryChart
                v-if="weeklyDatasets.length"
                :title="$t('metric_history.chart.weekly.title')"
                :description="$t('metric_history.chart.weekly.description')"
                :labels="labels"
                :datasets="weeklyDatasets"
                percent
            />
            <HistoryChart
                v-if="attentionDatasets.length"
                :title="$t('metric_history.chart.attention.title')"
                :description="$t('metric_history.chart.attention.description')"
                :labels="labels"
                :datasets="attentionDatasets"
            />
        </template>
    </div>
</template>

<style scoped lang="scss">
.section-gap {
    margin-top: 2rem;
}
</style>
