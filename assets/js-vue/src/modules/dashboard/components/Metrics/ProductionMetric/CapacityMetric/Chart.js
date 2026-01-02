import { defineComponent } from 'vue'
import { Bar } from 'vue-chartjs'


export default defineComponent({
    name: "Chart",
    extends: Bar,
    props: {
        chartData: { type: Object, required: true },
        chartOptions: { type: Object, default: () => ({  }) }
    },
    mounted() {
        this.renderChart(this.chartData, this.chartOptions)
    }
})
