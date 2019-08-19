<template>

    <div>

        <div class="text-center" v-if="busy">
            <i class="fa fa-spinner fa-pulse fa-3x fa-fw"></i>
        </div>

        <div class="row" v-else>

            <div class="col">
                <Badge border-class="border-left-primary" title="Zamówienia w realizacji" text-class="text-primary" :value="summary.ordersInProduction"></Badge>
            </div>

            <div class="col">
                <Badge border-class="border-left-success" title="Zamówienia zrealizowane" text-class="text-success" :value="summary.ordersFinished"></Badge>
            </div>

            <div class="col">
                <Badge border-class="border-left-info" title="Suma współczynników dla zamówień w realizacji" text-class="text-info" :value="summary.factorsInProduction | toFixed(2)"></Badge>
            </div>

            <div class="col">
                <Badge border-class="border-left-warning" title="Suma współczynników dla zamówień zrealizowanych" text-class="text-warning" :value="summary.factorsFinished | toFixed(2)"></Badge>
            </div>

            <div class="col">
                <Badge border-class="border-left-primary" title="Suma współczynników dla wszystkich zamówień" text-class="text-primary" :value="(summary.factorsFinished + summary.factorsInProduction) | toFixed(2)"></Badge>
            </div>

            <div class="col">
                <Badge v-if="estimateFirstFreeDay() !== null" border-class="border-left-success" title="Planowany dzień zrealizowania wszystkich zamówień" text-class="text-success" :value="estimateFirstFreeDay()"></Badge>
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

                summary: {
                    ordersInProduction: 0,
                    ordersFinished: 0,
                    factorsInProduction: 0,
                    factorsFinished: 0
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
                    .then(({data}) => { this.summary = data; })
                    .finally(() => { this.busy = false; })
                ;
            },

            estimateFirstFreeDay() {
                if (this.summary.ordersInProduction === 0) {
                    return null;
                }

                let daysNum = moment().daysInMonth();
                // let factorPerDay = 30 / daysNum;
                let factorPerDay = 1.4;
                let daysToComplete = Math.ceil(this.summary.factorsInProduction * factorPerDay);
                let endDate = moment();
                let index = 0;

                while (index < daysToComplete) {
                    endDate = endDate.add(1, 'day');
                    if ([6, 7].indexOf(endDate.isoWeekday()) === -1) {
                        index++;
                    }
                }

                return endDate.format('YYYY-MM-DD');

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