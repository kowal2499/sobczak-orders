<template>

    <div>

        <badge :border-class="'border-left-primary'" class="mb-3">
            <template v-slot:body>
                <div class="align-items-center">
                    <div class="text-title font-weight-bold text-primary text-uppercase mb-1">{{ $t('Okres analizy') }}</div>

                    <div class="form-inline">

                        <div>
                            <select class="form-control" v-model="filters.month">
                                <option v-for="month in months" :value="month.number" v-text="$t(month.name)"></option>
                            </select>
                        </div>

                        <div class="ml-2">
                            <select class="form-control" v-model="filters.year">
                                <option value="2018">2018</option>
                                <option value="2019">2019</option>
                                <option value="2020">2020</option>
                            </select>
                        </div>

                    </div>

                </div>

            </template>
        </badge>

        <collapsible-card :title="$t('Analiza współczynnikowa')">
            <div class="row">
                <div class="col mt-3">
                    <factors-summary
                            :month="filters.month"
                            :year="filters.year"
                    ></factors-summary>
                </div>
            </div>
        </collapsible-card>

        <collapsible-card :title="'Analiza tradycyjna'">
            <div class="row">
                <div class="col mt-3">
                    <production-summary
                        :month="filters.month"
                        :year="filters.year"
                    ></production-summary>
                </div>
            </div>
        </collapsible-card>

    </div>

</template>

<script>
    import CollapsibleCard from "../base/CollapsibleCard";
    import Months from '../../services/months';
    import ProductionSummary from './widgets/ProductionSummary';
    import FactorsSummary from "./widgets/FactorsSummary";
    import Badge from "./widgets/Badge";

    import moment from 'moment';

    export default {

        name: "Dashboard",
        components: {CollapsibleCard, ProductionSummary, FactorsSummary, Badge},

        props: ['title'],

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