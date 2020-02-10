<template>

    <div>

        <div class="text-center" v-if="busy">
            <i class="fa fa-spinner fa-pulse fa-3x fa-fw"></i>
        </div>

        <div v-else>

            <div class="row">
                <Badge border-class="border-left-primary" :title="$t('Ilość dni roboczych')" text-class="text-primary" :value="calendar.workingDays"></Badge>
                <Badge border-class="border-left-primary" :title="$t('Limit współczynników w miesiącu')" text-class="text-primary" :value="calendar.factorLimit"></Badge>
            </div>

            <div class="row">

                    <Badge border-class="border-left-primary" :title="$t('ordersInRealisation')" text-class="text-primary" :value="production.ordersInProduction"></Badge>

                    <Badge border-class="border-left-success" :title="$t('ordersFinished')" text-class="text-success" :value="production.ordersFinished"></Badge>

                    <Badge v-if="canSeeProduction()" border-class="border-left-info" :title="$t('totalFactorsInRealisation')" text-class="text-info" :value="production.factorsInProduction | toFixed(2)"></Badge>

                    <Badge v-if="canSeeProduction()" border-class="border-left-warning" :title="$t('totalFactorsFinished')" text-class="text-warning" :value="production.factorsFinished | toFixed(2)"></Badge>

                    <Badge v-if="canSeeProduction()" border-class="border-left-primary" :title="$t('totalFactors')" text-class="text-primary" :value="(production.factorsFinished + production.factorsInProduction) | toFixed(2)"></Badge>

                    <Badge v-if="estimateFirstFreeDay() !== null" border-class="border-left-success" :title="$t('estimatedFinishAllOrdersDay')" text-class="text-success" :value="calendar.firstFreeDay"></Badge>

            </div>

        </div>

    </div>
</template>

<script>

    import Badge from "./Badge";
    import Api from '../../../api/widgets';
    import moment from 'moment';

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
                    workingDays: null,
                    factorLimit: null,
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