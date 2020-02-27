<template>
    <div>
        <div class="text-center" v-if="busy">
            <i class="fa fa-spinner fa-pulse fa-3x fa-fw"></i>
        </div>
        <div v-else class="d-flex flex-wrap align-items-start">

            <template v-if="stats.workingSchedule">

                <badge border-class="border-left-primary" :width="250">
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


                <badge border-class="border-left-info" :width="330">
                    <template v-slot:body>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="w-75 text-title font-weight-bold text-primary text-uppercase">{{ $t('totalFactors') }}</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ stats.allOrders.totalFactors }}</div>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="w-75 text-title font-weight-bold text-primary text-uppercase">{{ $t('Wykorzystane moce produkcyjne') }}</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ stats.allOrders.productionCapacity }} </div>
                        </div>
                    </template>
                </badge>

                <badge border-class="border-left-success" :width="250">
                    <template v-slot:body>
                        <div class="text-title text-center font-weight-bold text-primary text-uppercase mb-3">{{ $t('ordersFinished')}}</div>

                            <div class="d-flex justify-content-between align-items-center">
                                <div class="w-75 text-title font-weight-bold text-primary text-uppercase">{{ $t('Quantity') }}</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ stats.finishedOrders.quantity }}</div>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="w-75 text-title font-weight-bold text-primary text-uppercase">{{ $t('Factors summary') }}</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ stats.finishedOrders.factors }}</div>
                            </div>
                    </template>
                </badge>

                <badge border-class="border-left-warning" :width="250">
                    <template v-slot:body>
                        <div class="text-title text-center font-weight-bold text-primary text-uppercase mb-3">{{ $t('ordersInRealisation') }}</div>

                        <div class="d-flex justify-content-between align-items-center">
                            <div class="w-75 text-title font-weight-bold text-primary text-uppercase">{{ $t('Quantity') }}</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ stats.notFinishedOrders.quantity }}</div>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="w-75 text-title font-weight-bold text-primary text-uppercase">{{ $t('Factors summary') }}</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ stats.notFinishedOrders.factors }}</div>
                        </div>
                    </template>
                </badge>

            </template>
        </div>
    </div>
</template>

<script>
    import Badge from "./Badge";
    import Api from '../../../api/widgets';

    export default {
        name: "FactorsSummary",
        props: ['month', 'year'],
        components: {Badge},

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
                stats: {}
            }
        }
    }
</script>

<style scoped>

</style>