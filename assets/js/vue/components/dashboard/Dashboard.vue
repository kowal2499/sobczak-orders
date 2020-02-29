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
                                <option v-for="year of years" :value="year">{{ year }}</option>
                            </select>
                        </div>
                    </div>
                </div>

            </template>
        </badge>

        <collapsible-card :title="$t('Analiza współczynnikowa')" v-if="$user.isGranted('reports.factorAnalysis')">
            <div class="row">
                <div class="col mt-3">
                    <factors-summary
                            :month="filters.month"
                            :year="filters.year"
                    ></factors-summary>
                </div>
            </div>
        </collapsible-card>

        <collapsible-card :title="$t('Analiza domyślna')" v-if="$user.isGranted('reports.defaultAnalysis')">
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
    import _ from 'lodash';

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
            },

            years() {
                const startYear = 2019;
                const endYear = parseInt(moment().format('YYYY')) + 2;
                return _.range(startYear, endYear, 1)
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