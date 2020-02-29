<template>
    <div>
        <div class="text-center" v-if="busy">
            <i class="fa fa-spinner fa-pulse fa-3x fa-fw"></i>
        </div>
        <div v-else>

            <template v-if="stats.workingSchedule">

                <div class="row">
                    <div class="col-sm-5">

                        <badge border-class="border-left-primary" :width="'100%'">
                            <template v-slot:body>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="w-75 text-title font-weight-bold text-primary text-uppercase">{{ $t('Ilość dni roboczych') }}</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ stats.workingSchedule.workingDaysCount }}</div>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="w-75 text-title font-weight-bold text-primary text-uppercase">{{ $t('Limit współczynników w miesiącu') }}</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ stats.workingSchedule.factorsLimit }}</div>
                                </div>
                            </template>
                        </badge>

                        <badge border-class="border-left-info" :width="'100%'">
                            <template v-slot:body>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="w-75 text-title font-weight-bold text-primary text-uppercase">{{ $t('Wykorzystane moce produkcyjne') }}</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ stats.allOrders.productionCapacity }} %</div>
                                </div>
                            </template>
                        </badge>

                        <badge border-class="border-left-warning" :width="'100%'">
                            <template v-slot:body>
                                <div class="table-responsive">
                                    <table class="table table-borderless">
                                        <thead>
                                        <tr>
                                            <th></th>
                                            <th class="text-title text-center font-weight-bold text-primary text-uppercase">{{ $t('ordersFinished') }}</th>
                                            <th class="text-title text-center font-weight-bold text-primary text-uppercase">{{ $t('ordersInRealisation') }}</th>
                                            <th class="text-title text-center font-weight-bold text-primary text-uppercase">Total</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <tr>
                                            <th class="text-title text-left font-weight-bold text-primary text-uppercase">{{ $t('Quantity') }}</th>
                                            <td>{{ stats.finishedOrders.quantity }}</td>
                                            <td>{{ stats.notFinishedOrders.quantity }}</td>
                                            <td>{{ stats.allOrders.total }}</td>
                                        </tr>
                                        <tr>
                                            <th class="text-title text-left font-weight-bold text-primary text-uppercase">{{ $t('Factors summary') }}</th>
                                            <td>{{ stats.finishedOrders.factors }}</td>
                                            <td>{{ stats.notFinishedOrders.factors }}</td>
                                            <td>{{ stats.allOrders.totalFactors }}</td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </template>
                        </badge>
                    </div>

                    <div class="col-sm-7">
                        <bar-chart :chartdata="chartData" :options="chartOptions" :height="400"/>
                    </div>

                </div>
            </template>
        </div>
    </div>
</template>

<script>
    import Badge from "./Badge";
    import Api from '../../../api/widgets';
    import BarChart from "../../charts/BarChart.vue";

    export default {
        name: "FactorsSummary",
        props: ['month', 'year'],
        components: {Badge, BarChart},

        watch: {
            month() {
                this.fetchData();
            },

            year() {
                this.fetchData();
            }
        },

        mounted() {
            this.fetchData()
        },

        computed: {
            chartData() {
                if (!this.stats.plan) {
                    return [];
                }

                let labels = this.stats.plan.map(plan => plan.month);
                let datasets = [
                    {
                        backgroundColor: '#4e73df',
                        label: this.$t('Wykorzystane moce produkcyjne'),
                        data: this.stats.plan.map(plan => plan.usedCapacity)
                    }
                ];

                return {
                    labels,
                    datasets
                }
            }
        },

        methods: {
            fetchData() {
                this.busy = true;

                Api.factorsSummary(this.month, this.year)
                    .then(({data}) => {
                        this.stats = data;
                    })
                    .finally(() => { this.busy = false; })
                ;
            }
        },

        data() {
            return {
                busy: true,
                stats: {},
                chartOptions: {
                    title: {
                        text: this.$t('Wykorzystane moce produkcyjne') + ' [%]',
                        display: true,
                    },
                    scales: {
                        xAxes: [{
                            ticks: {
                                beginAtZero: true,
                                min: 0,
                                stepSize: 25,
                                suggestedMax: 100
                            }
                        }],
                    },
                }
            }
        }
    }
</script>

<style scoped lang="scss">

    thead {
        th {
            width: 80px;
            vertical-align: middle;
        }
    }

    th {
        vertical-align: middle;
    }

    thead > tr, tr:not(:last-child) {
        border-bottom: 1px solid #efefef;
    }

    tbody {
        td {
            text-align: center;
            font-weight: 500;
            font-size: 1.25rem;
            color: slategray;
            border-top: none !important;
        }
    }

</style>