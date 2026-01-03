import { defineComponent } from 'vue'
import { Bar } from 'vue-chartjs'

export default defineComponent({
    name: "Chart",
    extends: Bar,
    props: {
        chartData: { type: Object, required: true },
        chartOptions: { type: Object, default: () => ({}) },
        clickHandler: { type: Function, default: null },
    },
    mounted() {
        this.renderChart(this.chartData, this.chartOptions)

        const chart = this.$data._chart
        if (chart) {
            chart.options.onClick = (evt, activeElements) => {
                if (!activeElements || activeElements.length === 0) return
                const first = activeElements[0]
                const index = first._index
                const datasetIndex = first._datasetIndex
                const payload = { index, datasetIndex }
                this.$emit('bar-click', payload)
            }
            chart.update()
        }
    }
})
