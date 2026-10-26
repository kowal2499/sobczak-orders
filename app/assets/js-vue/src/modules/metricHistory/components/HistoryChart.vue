<script>
import { defineComponent } from 'vue'
import LineChart from './LineChart.js'

export default defineComponent({
    name: 'HistoryChart',
    components: { LineChart },
    props: {
        title: { type: String, required: true },
        description: { type: String, default: null },
        labels: { type: Array, required: true },
        datasets: { type: Array, required: true },
        percent: { type: Boolean, default: false },
        // indeksy dni uzupełnionych wstecz - liczone na późniejszych danych, nie zamrożone
        backfilled: { type: Set, default: () => new Set() },
    },
    computed: {
        isEmpty() {
            return this.datasets.every(dataset => dataset.data.every(value => value === null))
        },
        chartData() {
            return { labels: this.labels, datasets: this.datasets }
        },
        chartOptions() {
            const format = value => this.format(value)
            const backfilled = this.backfilled
            const backfillNote = this.$t('metric_history.backfill_note')

            return {
                responsive: true,
                maintainAspectRatio: false,
                spanGaps: false,
                legend: { position: 'bottom', labels: { boxWidth: 12 } },
                scales: {
                    yAxes: [{
                        ticks: { beginAtZero: true, callback: format },
                    }],
                },
                tooltips: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        label: (item, data) => `${data.datasets[item.datasetIndex].label}: ${format(item.yLabel)}`,
                        footer: items => (items.length && backfilled.has(items[0].index) ? backfillNote : ''),
                    },
                },
            }
        },
    },
    methods: {
        format(value) {
            if (value === null || value === undefined) {
                return '-'
            }
            const number = new Intl.NumberFormat(this.$i18n.locale === 'en' ? 'en-US' : 'pl-PL', {
                maximumFractionDigits: 2,
            }).format(value)

            return this.percent ? `${number}%` : number
        },
    },
})
</script>

<template>
    <SectionBlock class="section-gap">
        <h6 class="font-weight-bold mb-1">{{ title }}</h6>
        <p v-if="description" class="text-muted small mb-2">{{ description }}</p>
        <div v-if="isEmpty" class="text-muted small py-4">{{ $t('metric_history.no_data_chart') }}</div>
        <LineChart v-else :chart-data="chartData" :chart-options="chartOptions" :style="{ height: '280px' }" />
    </SectionBlock>
</template>
