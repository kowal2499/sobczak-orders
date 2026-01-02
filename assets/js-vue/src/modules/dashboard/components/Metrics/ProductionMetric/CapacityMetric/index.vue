<script>
import { defineComponent } from 'vue'
import ProductionMetricMixin from '../ProductionMetricMixin'
import DepartmentMetricMixin from '../DepartmentMetricMixin'
import BaseMetric from '../../BaseMetric.js'
import MetricLayout from '../../MetricLayout.vue'
import Chart from './Chart.js'

const BAR_COLORS = {
    Klejenie:     { bg: 'rgba(78, 121, 167, 0.7)',  border: '#4E79A7' }, // niebieski
    CNC:          { bg: 'rgba(242, 142, 43, 0.7)',  border: '#F28E2B' }, // pomarańcz
    Szlifowanie:  { bg: 'rgba(89, 161, 79, 0.7)',   border: '#59A14F' }, // zielony
    Lakierowanie: { bg: 'rgba(225, 87, 89, 0.7)',   border: '#E15759' }, // czerwony
    Pakowanie:    { bg: 'rgba(118, 183, 178, 0.7)', border: '#76B7B2' }, // morski
    _fallback:    { bg: 'rgba(128, 128, 128, 0.5)', border: '#808080' }  // zapasowy
}

export default defineComponent({
    name: "CapacityMetric",
    extends: BaseMetric,
    mixins: [ProductionMetricMixin, DepartmentMetricMixin],
    components: {
        MetricLayout,
        Chart,
    },
    computed: {
        perDepartmentData() {
            return this.aggregateByDepartment(this.data)
        },
        perAgreementData() {
            return this.mapDetails(this.data)
        },
        labels() {
            return this.perDepartmentData.map(({name}) => name)
        },
        datasets() {
            const bg = this.labels.map(l => (BAR_COLORS[l] || BAR_COLORS._fallback).bg)
            const border = this.labels.map(l => (BAR_COLORS[l] || BAR_COLORS._fallback).border)
            return [
                {
                    data: this.perDepartmentData.map(({ value }) => value),
                    backgroundColor: bg,   // Chart.js v2: tablica kolorów per słupek
                    borderColor: border,
                    borderWidth: 1
                }
            ]
        }
    }
})

</script>

<template>
    <MetricLayout :is-busy="isBusy" class="border-left-success">
        <template #title>
            Obłożenie działów produkcji
        </template>

        <Chart
            :style="{height: '300px'}"
            :chartOptions="{
                responsive: true,
                maintainAspectRatio: false,
                legend: { display: false },
                scales: {
                    yAxes: [{
                      type: 'linear',
                      ticks: { min: 0, beginAtZero: true }
                    }]
                }
            }"
            :chart-data="{
                labels,
                datasets
            }"
        />
    </MetricLayout>
</template>

<style scoped lang="scss">

</style>