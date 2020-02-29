<script>
    import {HorizontalBar} from 'vue-chartjs'

    export default {
        extends: HorizontalBar,
        props: ['chartdata', 'options'],
        name: "BarChart",
        mounted () {
            this.renderChart(this.chartdata, {...this.defaultOptions, ...this.options})
        },

        computed: {
          defaultOptions() {
              return {
                  responsive: true,
                  maintainAspectRatio: false,
                  legend: {
                      position: 'bottom',
                  },
                  hover: {
                      mode: 'label'
                  },

              }
          }
        }
    }
</script>