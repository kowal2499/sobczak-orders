<template>
    <collapsible-card :title="$t('dashboard.title')">
        <b-form inline class="mb-4">
            <b-form-select v-model="filters.year" :options="yearsOptions" class="mr-3" />
            <b-form-select v-model="filters.month" :options="monthsOptions" class="mr-3" />
        </b-form>

        <div class="d-flex mb-4" v-for="group in metricsLayout">
            <badge
                v-for="metric in group"
                :key="metric.id"
                :metric="metric"
                @clicked="activeMetricId = $event"
            />
        </div>

        <details-modal v-model="showModal" :records-promise="modalRecordsPromise" :title="activeMetricTitle"/>
    </collapsible-card>
</template>

<script>
import CollapsibleCard from "../../components/base/CollapsibleCard";
import { MONTHS, dateToString, firstDay, lastDay } from "../../services/datesService";
import {
    getAgreementLinesSummary,
    getOldSummary
} from "./repository";
import Badge from './components/Badge';
import DetailsModal from "./components/DetailsModal";
import MetricsDefinitions from "./MetricsDefinitions";

const START_YEAR = 2018;

export default {
    name: 'Dashboard2',
    components: {
        CollapsibleCard,
        Badge,
        DetailsModal
    },
    computed: {
        months: () => MONTHS,
        years() {
            const currentYear = new Date().getFullYear();
            const yearsRange = currentYear - START_YEAR + 2;
            return Array.from({length: yearsRange }, (item, idx) => idx + START_YEAR)
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
            return this.filters.year && this.filters.month
                ? dateToString(firstDay(this.filters.year, this.filters.month))
                : null
        },
        dateRangeEnd() {
            return this.filters.year && this.filters.month
                ? dateToString(lastDay(this.filters.year, this.filters.month))
                : null
        },
        showModal: {
            set(v) {
                this.activeMetricId = !!v;
            },
            get() {
                return !!this.activeMetricId;
            }
        },
        modalRecordsPromise() {
            const metric = this.metrics.find(metric => metric.id === this.activeMetricId);
            return metric
                ? metric.fetchDetails(this.dateRangeStart, this.dateRangeEnd)
                : Promise.resolve()
        },
        metricsLayout() {
            const grouped = {}
            for (let metric of this.metrics) {
                let groupId = metric.groupId
                if (!grouped[groupId]) {
                    grouped[groupId] = [];
                }
                grouped[groupId].push(metric)
            }
            return Object.values(grouped)
        },
        activeMetric() {
            return this.metrics.find(metric => metric.id === this.activeMetricId)
        },
        activeMetricTitle() {
            return this.activeMetric ? this.$t(this.activeMetric.title) : '';
        }
    },
    mounted() {
        const today = new Date();
        this.filters.year = today.getFullYear();
        this.filters.month = today.getMonth();
    },
    methods: {
        setMetricsData(data) {
            const metricsMap = this.metrics.reduce((carry, item) => {
                carry[item.id] = item
                return carry
            }, {})

            Object.keys(data).forEach(key => {
                if (metricsMap.hasOwnProperty(key)) {
                    metricsMap[key].value = data[key]
                    metricsMap[key].busy = false
                }
            })
        }
    },
    watch: {
        filters: {
            deep: true,
            handler() {
               this.metrics.forEach(metric => metric.busy = true)

                getAgreementLinesSummary(this.dateRangeStart, this.dateRangeEnd)
                    .then(({data}) => this.setMetricsData(data))

                getOldSummary(this.dateRangeStart, this.dateRangeEnd)
                    .then(({data}) => this.setMetricsData(data))
            }
        }
    },
    data: () => ({
        filters: {
            month: null,
            year: null
        },
        agreementLinesSummaryData: {},
        activeMetricId: '',
        isModalOn: false,
        metrics: MetricsDefinitions
    }),
}
</script>

<style scoped lang="scss">
 .clickable-metric {
     display: inline-block;
     &:hover {
         cursor: pointer;
         border-bottom: 1px solid #365DCD;
     }
 }
</style>