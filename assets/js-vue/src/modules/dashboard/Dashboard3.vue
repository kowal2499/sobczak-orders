<template>
    <collapsible-card :title="$t('dashboard.title')">
        <b-form inline class="mb-4">
            <b-form-select v-model="filters.year" :options="yearsOptions" class="mr-3" />
            <b-form-select v-model="filters.month" :options="monthsOptions" class="mr-3" />
        </b-form>

        <div class="row">
            <div class="col-md-4 col-lg-3">
                <WorkingDaysMetric
                    :is-busy="sourcesState.src01.isBusy"
                    :data="sourcesState.src01.data"
                />
            </div>
            <div class="col-md-4 col-lg-3">
                <FactorsLimitMetric
                    :is-busy="sourcesState.src01.isBusy"
                    :data="sourcesState.src01.data"
                />
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 col-lg-3" v-if="canDashboardMetrics">
                <OrdersCountMetric
                    :is-busy="sourcesState.src02.isBusy"
                    :data="sourcesState.src02.data"
                    :filters="{ dateStart: dateRangeStart, dateEnd: dateRangeEnd }"
                    status="orders_pending"
                    class="border-left-primary"
                />
            </div>
            <div class="col-md-4 col-lg-3" v-if="canDashboardMetrics">
                <OrdersCountMetric
                    :is-busy="sourcesState.src02.isBusy"
                    :data="sourcesState.src02.data"
                    :filters="{ dateStart: dateRangeStart, dateEnd: dateRangeEnd }"
                    status="orders_finished"
                    class="border-left-success"
                />
            </div>
            <div class="col-md-4 col-lg-3" v-if="canDashboardMetrics">
                <CompletionDateMetric
                    :is-busy="sourcesState.src01.isBusy"
                    :data="sourcesState.src01.data"
                />
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 col-lg-3" v-if="canDashboardMetrics">
                <DepartmentsFactorMetric
                    :is-busy="sourcesState.src03.isBusy"
                    :data="sourcesState.src03.data"
                />
            </div>
        </div>

    </collapsible-card>
</template>

<script>
import CollapsibleCard from "../../components/base/CollapsibleCard";
import { MONTHS, dateToString, firstDay, lastDay } from "../../services/datesService";
import WorkingDaysMetric from "./components/Metrics/WorkingDaysMetric.vue";
import FactorsLimitMetric from "./components/Metrics/FactorsLimitMetric.vue"
import OrdersCountMetric from "./components/Metrics/OrdersCountMetric/index.vue"
import CompletionDateMetric from "./components/Metrics/CompletionDateMetric.vue"
import DepartmentsFactorMetric from "./components/Metrics/DepartmentsFactorMetric/index.vue";
import PRIVILEGES from "../../definitions/userRoles";

import {
    getAgreementLinesSummary,
    getProductionTasksCompletionSummaryNew,
    getOldSummary
} from "./repository";

const START_YEAR = 2018;

const DATA_SOURCES = [
    {
        id: 'src01',
        fetcher: getOldSummary,
    },
    {
        id: 'src02',
        fetcher: getAgreementLinesSummary,
        grant: PRIVILEGES.CAN_DASHBOARD_METRICS_VIEW,
    },
    {
        id: 'src03',
        fetcher: getProductionTasksCompletionSummaryNew,
        grant: PRIVILEGES.CAN_DASHBOARD_METRICS_VIEW,
    }
]

export default {
    name: 'Dashboard3',

    components: {
        CollapsibleCard,
        WorkingDaysMetric,
        FactorsLimitMetric,
        CompletionDateMetric,
        OrdersCountMetric,
        DepartmentsFactorMetric,
    },

    computed: {
        months: () => MONTHS,
        years() {
            const currentYear = new Date().getFullYear();
            const yearsRange = currentYear - START_YEAR + 2;
            return Array.from({length: yearsRange }, (item, idx) => idx + START_YEAR).reverse()
        },
        yearsOptions() {
            return [
                { value: null, text: this.$t('dashboard.year_placeholder'), disabled: true },
                ...this.years.map(y => ({ value: y, text: y }))
            ];
        },
        monthsOptions() {
            return [
                { value: null, text: this.$t('dashboard.month_placeholder'), disabled: true },
                ...MONTHS.map(m => ({ value: m.number, text: this.$t(m.name) }))
            ];
        },
        dateRangeStart() {
            return this.filters.year !== null && this.filters.month !== null
                ? dateToString(firstDay(this.filters.year, this.filters.month))
                : null
        },
        dateRangeEnd() {
            return this.filters.year !== null && this.filters.month !== null
                ? dateToString(lastDay(this.filters.year, this.filters.month))
                : null
        },
        canDashboardMetrics() {
            return this.$user.can(PRIVILEGES.CAN_DASHBOARD_METRICS_VIEW);
        }
    },

    created() {
        this.sourcesState = DATA_SOURCES.map(source => ({
            id: source.id,
            isBusy: false,
            error: null,
            data: null,
        })).reduce((acc, item) => {
            acc[item.id] = item
            return acc
        }, {})
    },

    mounted() {
        const today = new Date();
        this.filters.year = today.getFullYear();
        this.filters.month = today.getMonth();
    },

    watch: {
        filters: {
            deep: true,
            handler() {
                DATA_SOURCES.forEach(source => {
                    if (source.grant && !this.$user.can(source.grant)) {
                        return;
                    }
                    this.sourcesState[source.id].isBusy = true;
                    this.sourcesState[source.id].error = null;
                    source.fetcher(this.dateRangeStart, this.dateRangeEnd)
                        .then(({data}) => {
                            this.sourcesState[source.id].data = data;
                        })
                        .catch((error) => {
                            this.sourcesState[source.id].error = error;
                        })
                        .finally(() => {
                            this.sourcesState[source.id].isBusy = false;
                        });
                })
            }
        }
    },
    data: () => ({
        sourcesState: {},

        filters: {
            month: null,
            year: null
        },
    }),
}
</script>

<style scoped lang="scss">
</style>