<template>
    <div>
        <div class="row">
            <div class="col">
                <div class="card border-0 mb-3">
                    <div class="form-inline">
                        <div>
                            <select class="form-control" v-model="filters.month">
                                <option v-for="month in months" :value="month.number" v-text="$t(month.name)"></option>
                            </select>
                        </div>

                        <div class="ml-2">
                            <select class="form-control" v-model="filters.year">
                                <option v-for="year in years" :value="year">{{ year }}</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <production-summary
                    :month="filters.month"
                    :year="filters.year"
                ></production-summary>
            </div>
        </div>
    </div>
</template>

<script>
    import Months from '../../services/months';
    import ProductionSummary from './widgets/ProductionSummary';

    import moment from 'moment';

    export default {

        name: "Dashboard",
        components: { ProductionSummary },

        data() {
            return {
                filters: {
                    month: null,
                    year: null
                }
            }
        },

        computed: {
            months() {
                return Months.all();
            },
            years() {
                const endYear = Number((new moment).format('YYYY')) + 1;
                const years = [];
                for (let year = 2018; year <= endYear; year++) {
                    years.push(year);
                }
                return years;
            }
        },

        created() {
            this.filters.month = Number((new moment).format('MM'));
            this.filters.year = Number((new moment).format('YYYY'));
        }

    }
</script>

<style scoped>

</style>