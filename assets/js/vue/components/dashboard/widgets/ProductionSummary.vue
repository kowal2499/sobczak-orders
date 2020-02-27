<template>
    <div>
        <div class="text-center" v-if="busy">
            <i class="fa fa-spinner fa-pulse fa-3x fa-fw"></i>
        </div>

        <div v-else>
            <div class="d-flex flex-wrap">
                <Badge border-class="border-left-primary" :title="$t('ordersInRealisation')" :width="150" :value="production.ordersInProduction"></Badge>
                <Badge border-class="border-left-success" :title="$t('ordersFinished')" :width="150" :value="production.ordersFinished"></Badge>
                <Badge v-if="canSeeProduction()" border-class="border-left-info" :width="150" :title="$t('totalFactorsInRealisation')" :value="production.factorsInProduction | toFixed(2)"></Badge>
                <Badge v-if="canSeeProduction()" border-class="border-left-warning" :width="150" :title="$t('totalFactorsFinished')" :value="production.factorsFinished | toFixed(2)"></Badge>
                <Badge v-if="canSeeProduction()" border-class="border-left-primary" :width="150" :title="$t('totalFactors')" :value="(production.factorsFinished + production.factorsInProduction) | toFixed(2)"></Badge>
                <Badge v-if="estimateFirstFreeDay() !== null" border-class="border-left-success" :width="150" :title="$t('estimatedFinishAllOrdersDay')" :value="calendar.firstFreeDay"></Badge>
            </div>
        </div>
    </div>
</template>

<script>

    import Badge from "./Badge";
    import Api from '../../../api/widgets';

    export default {
        name: "ProductionSummary",

        components: {Badge},

        props: ['month', 'year'],

        data() {
            return {
                busy: true,

                production: {
                    ordersInProduction: 0,
                    ordersFinished: 0,
                    factorsInProduction: 0,
                    factorsFinished: 0,
                },

                calendar: {
                    firstFreeDay: null,
                }
            }
        },

        watch: {
            month() {
                this.fetchData();
            },

            year() {
                this.fetchData();
            }
        },

        mounted() {
            this.fetchData();
        },

        methods: {

            fetchData() {
                this.busy = true;

                Api.productionSummary(this.month, this.year)
                    .then(({data}) => {
                        this.production = {
                            ...this.production,
                            ...data.production
                        };
                        this.calendar = {
                            workingDays: data.workingDays,
                            factorLimit: data.factorLimit,
                            firstFreeDay: data.firstFreeDay,
                        };
                    })
                    .finally(() => { this.busy = false; })
                ;
            },

            estimateFirstFreeDay() {
                // if (this.summary.ordersInProduction === 0) {
                //     return null;
                // }
                //
                // let daysNum = moment().daysInMonth();
                // // let factorPerDay = 30 / daysNum;
                // let factorPerDay = 1.4;
                // let daysToComplete = Math.ceil(this.summary.factorsInProduction * factorPerDay);
                // let endDate = moment();
                // let index = 0;
                //
                // while (index < daysToComplete) {
                //     endDate = endDate.add(1, 'day');
                //     if ([6, 7].indexOf(endDate.isoWeekday()) === -1) {
                //         index++;
                //     }
                // }
                // return endDate.format('YYYY-MM-DD');
            },

            canSeeProduction() {
                return this.$user.can(this.$privilages.CAN_PRODUCTION);
            }
        },

        filters: {
            toFixed(value, digits) {
                return (value - parseInt(value)) > 0 ? value.toFixed(digits) : parseInt(value);
            }
        }


    }
</script>

<style scoped>

</style>