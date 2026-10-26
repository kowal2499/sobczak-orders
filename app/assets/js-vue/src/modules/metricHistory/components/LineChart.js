import { defineComponent } from 'vue'
import { Line } from 'vue-chartjs'

export default defineComponent({
    name: 'LineChart',
    extends: Line,
    props: {
        chartData: { type: Object, required: true },
        chartOptions: { type: Object, default: () => ({}) },
    },
    watch: {
        chartData(newVal, oldVal) { if (newVal !== oldVal) this.renderChart(this.chartData, this.chartOptions) },
        chartOptions(newVal, oldVal) { if (newVal !== oldVal) this.renderChart(this.chartData, this.chartOptions) },
    },
    mounted() {
        this.renderChart(this.chartData, this.chartOptions)
    },
})
