<script>
import { defineComponent } from 'vue'
import { dateToString, firstDay, lastDay } from '@/services/datesService'
import { getProductionTasksOnTimeSummary } from '@/modules/dashboard/repository'
import BaseMetric from '@/modules/dashboard/components/Metrics/BaseMetric'
import ProductionMetricMixin from '@/modules/dashboard/components/Metrics/ProductionMetric/ProductionMetricMixin'
import OnTimeDetails from '@/modules/dashboard/components/Metrics/ProductionMetric/components/OnTimeDetails.vue'
import fields from '@/modules/dashboard/components/Metrics/ProductionMetric/fields'
import SidebarLayout from '@/components/layout/SidebarLayout.vue'
import SidebarNavbar from '@/components/layout/SidebarNavbar.vue'

/**
 * Szczegóły raportu "Ukończone zadania produkcyjne (w terminie)" za miesiąc rozliczenia -
 * dokładnie ta sama tabela, którą kierownik zna z pulpitu, razem z wyszukiwarką i eksportem.
 *
 * Widełki terminowości bierzemy z okresu, nie z pulpitu. Rozliczenie ma pokazywać zadania takie,
 * jakie policzył backend zakładający ten miesiąc - inaczej sumy w szufladzie nie zgadzałyby się
 * z kolumną "Wyliczono".
 */
export default defineComponent({
    name: 'OnTimeReportDetails',
    mixins: [BaseMetric, ProductionMetricMixin],
    components: { OnTimeDetails, SidebarLayout, SidebarNavbar },
    props: {
        period: {
            type: Object,
            required: true,
        },
        height: {
            type: [String, Number],
            default: null,
        },
    },
    data: () => ({
        loading: false,
    }),
    computed: {
        range() {
            const month = this.period.month - 1

            return {
                start: dateToString(firstDay(this.period.year, month)),
                end: dateToString(lastDay(this.period.year, month)),
            }
        },
    },
    methods: {
        async load() {
            this.loading = true
            try {
                const { data } = await getProductionTasksOnTimeSummary(
                    this.range.start,
                    this.range.end,
                    this.period.toleranceDays
                )
                this.innerData = Array.isArray(data) && data.length
                    ? this.mapDetails(data).map(item => this.addSearchKey(item))
                    : []
            } catch {
                this.innerData = []
                this.$flash.danger(this.$t('bonus_settlement.error.load_details'))
            } finally {
                this.loading = false
            }
        },
        onExportExcel() {
            return this.exportExcel(this.$t('dashboard.tasksCompletedOnTime'), fields, this.innerData)
        },
    },
    mounted() {
        this.load()
    },
})
</script>

<template>
    <SidebarLayout>
        <template #header>
            <SidebarNavbar @search="q = $event" @exportExcel="onExportExcel" />
        </template>
        <template #content>
            <div v-if="loading" class="text-center text-muted my-4">
                <i class="fa fa-spinner fa-spin fa-2x"></i>
            </div>
            <OnTimeDetails
                v-else
                :data="filteredInnerData"
                :height="height"
                :report-range="range"
                class="px-2 pb-2"
                @saved="load"
            />
        </template>
    </SidebarLayout>
</template>
