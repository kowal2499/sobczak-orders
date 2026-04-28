<script>
import { defineComponent } from 'vue'
import ProductionMetricMixin from '../ProductionMetricMixin'
import DepartmentMetricMixin from '../DepartmentMetricMixin'
import Sidebar from '@/components/base/Sidebar.vue'
import DetailsDepartment from '../components/DetailsDepartment.vue'
import SidebarNavbar from '@/components/layout/SidebarNavbar'
import BaseMetric from '../../BaseMetric.js'
import MetricLayout from '../../MetricLayout.vue'
import SidebarLayout from '@/components/layout/SidebarLayout.vue'
import Chart from './Chart.js'
import { DPT_GLUEING, DPT_CNC, DPT_GRINDING, DPT_LACQUERING, DPT_PACKING, DEPARTMENTS } from '@/helpers'
import exportFields from './exportFields'
import { getDepartmentsCapacity } from '../../../../repository'

const BAR_COLORS = {
    [DPT_GLUEING]:     { bg: 'rgba(78, 121, 167, 0.7)',  border: '#4E79A7' },
    [DPT_CNC]:         { bg: 'rgba(242, 142, 43, 0.7)',  border: '#F28E2B' },
    [DPT_GRINDING]:    { bg: 'rgba(89, 161, 79, 0.7)',   border: '#59A14F' },
    [DPT_LACQUERING]:  { bg: 'rgba(225, 87, 89, 0.7)',   border: '#E15759' },
    [DPT_PACKING]:     { bg: 'rgba(118, 183, 178, 0.7)', border: '#76B7B2' },
    _fallback:         { bg: 'rgba(128, 128, 128, 0.5)', border: '#808080' },
}

export default defineComponent({
    name: "CapacityMetric",
    extends: BaseMetric,
    mixins: [ProductionMetricMixin, DepartmentMetricMixin],
    components: {
        MetricLayout,
        Chart,
        Sidebar,
        DetailsDepartment,
        SidebarNavbar,
        SidebarLayout,
    },
    props: {
        dateStart: { type: String, default: null },
        dateEnd: { type: String, default: null },
    },
    watch: {
        data: {
            deep: true,
            handler() {
                // set inner data
                if (!Array.isArray(this.data)) {
                    return
                }
                if (!this.data.length) {
                    return
                }
                this.innerData = this.mapDetails(this.data)
                    .map(item => this.addSearchKey(item))
                ;
            }
        },
        showForecast(value) {
            if (value) {
                this.fetchForecast()
            } else {
                this.forecastData = null
            }
        },
        dateStart() {
            if (this.showForecast) {
                this.fetchForecast()
            }
        },
        dateEnd() {
            if (this.showForecast) {
                this.fetchForecast()
            }
        },
    },
    computed: {
        ghostOnlyData() {
            if (!Array.isArray(this.forecastData)) {
                return []
            }
            return this.forecastData.filter(item => item.isGhost === true)
        },
        perDepartmentData() {
            return this.aggregateByDepartment(this.data)
        },
        perDepartmentForecastData() {
            return this.aggregateByDepartment(this.ghostOnlyData)
        },
        perDptAgreementData() {
            if (!this.activeDepartmentSlug) {
                return []
            }
            const dptKey = `involved_${this.activeDepartmentSlug}`
            return this.filteredInnerData
                .map(data => {
                    const {involved_dpt01, involved_dpt02, involved_dpt03, involved_dpt04, involved_dpt05, involved_dpt06, ...rest} = data
                    return {...rest, data: data[dptKey] || {} }
                })
                .filter(record => record.data.factor !== null)
                .sort((a, b) => a.data.production.dateStart ? (new Date(a.data.production.dateStart) > new Date(b.data.production.dateStart) ? 1 : -1) : 0)
        },
        activeDepartmentName() {
            const dpt = DEPARTMENTS.find(d => d.slug === this.activeDepartmentSlug)
            return dpt ? dpt.name : this.activeDepartmentSlug
        },
        labels() {
            return this.perDepartmentData.map(({slug}) => {
                const dpt = DEPARTMENTS.find(d => d.slug === slug)
                return dpt ? dpt.name : slug
            })
        },
        datasets() {
            const bg = this.perDepartmentData.map(({slug}) => (BAR_COLORS[slug] || BAR_COLORS._fallback).bg)
            const border = this.perDepartmentData.map(({slug}) => (BAR_COLORS[slug] || BAR_COLORS._fallback).border)
            const main = {
                label: this.$t('dashboard.capacityMetric'),
                data: this.perDepartmentData.map(({ value }) => value),
                backgroundColor: bg,
                borderColor: border,
                borderWidth: 1,
                stack: 'capacity',
            }
            if (!this.showForecast) {
                return [main]
            }
            const forecastBg = this.perDepartmentForecastData.map(({slug}) => {
                const c = (BAR_COLORS[slug] || BAR_COLORS._fallback).bg
                return c.replace(/rgba?\(([^)]+)\)/, (_, inner) => {
                    const parts = inner.split(',').map(s => s.trim())
                    return `rgba(${parts[0]}, ${parts[1]}, ${parts[2]}, 0.25)`
                })
            })
            const forecastBorder = this.perDepartmentForecastData.map(({slug}) => (BAR_COLORS[slug] || BAR_COLORS._fallback).border)
            return [
                main,
                {
                    label: this.$t('dashboard.forecastLabel'),
                    data: this.perDepartmentForecastData.map(({ value }) => value),
                    backgroundColor: forecastBg,
                    borderColor: forecastBorder,
                    borderWidth: 1,
                    borderDash: [4, 4],
                    stack: 'capacity',
                }
            ]
        }
    },
    methods: {
        onBarClick({ index }) {
            this.activeDepartmentSlug = this.perDepartmentData[index].slug
            this.beforeOpen()
            this.showSidebar = true
        },
        beforeOpen() {
            this.q = null
        },
        onExportExcel() {
            return this.exportExcel('Obłożenie działów produkcji', exportFields, this.perDptAgreementData)
        },
        toggleForecast() {
            this.showForecast = !this.showForecast
        },
        async fetchForecast() {
            if (!this.dateStart || !this.dateEnd) {
                return
            }
            this.forecastBusy = true
            try {
                const { data } = await getDepartmentsCapacity(this.dateStart, this.dateEnd, { includeGhost: true })
                this.forecastData = Array.isArray(data) ? data : []
            } finally {
                this.forecastBusy = false
            }
        },
    },
    data: () => ({
        showSidebar: false,
        activeDepartmentSlug: null,
        showForecast: false,
        forecastBusy: false,
        forecastData: null,
    })
})

</script>

<template>
    <MetricLayout :is-busy="isBusy || forecastBusy" class="border-left-success">
        <template #title>
            <div class="d-flex align-items-center justify-content-between flex-wrap">
                <span>Obłożenie działów produkcji</span>
            </div>
        </template>

        <template #description>
            <p v-html="$t('dashboard.descriptions.capacity.p1')"></p>
            <p v-html="$t('dashboard.descriptions.capacity.p2')"></p>
            <p v-html="$t('dashboard.descriptions.capacity.p3')"></p>
            <p v-html="$t('dashboard.descriptions.capacity.p4')"></p>
            <p v-html="$t('dashboard.descriptions.capacity.p5')"></p>
        </template>

        <Chart
            class="mt-2"
            :style="{height: '300px'}"
            :chartOptions="{
                responsive: true,
                maintainAspectRatio: false,
                legend: { display: showForecast },
                scales: {
                    xAxes: [{ stacked: true }],
                    yAxes: [{
                      stacked: true,
                      type: 'linear',
                      ticks: { min: 0, beginAtZero: true }
                    }]
                },
                tooltips: {
                    callbacks: {
                        label: (tooltipItem) => parseFloat(tooltipItem.yLabel.toFixed(2)).toString()
                    }
                },
                onHover: (evt, activeElements) => {
                    const el = evt && evt.target
                    if (!el) { return }
                    el.style.cursor = (activeElements && activeElements.length) ? 'pointer' : 'default'
                }
            }"
            :chart-data="{ labels, datasets }"
            @bar-click="onBarClick"
        />
        <b-form-checkbox
            :checked="showForecast" @change="toggleForecast" switch size="sm"
        >
            {{ $t('dashboard.forecastLabel') }}
        </b-form-checkbox>

        <Sidebar
            :title="`Szczegóły obłożenia działu - ${activeDepartmentName}`"
            v-model="showSidebar"
            sidebar-class="size-100 size-lg-50"
        >
            <template #sidebar-content>
                <SidebarLayout>
                    <template #header>
                        <SidebarNavbar
                            @search="q = $event"
                            @exportExcel="onExportExcel"
                        />
                    </template>
                    <template #content>
                        <DetailsDepartment
                            v-for="record in perDptAgreementData"
                            :key="record.id"
                            :record="record"
                        />
                    </template>
                </SidebarLayout>
            </template>
        </Sidebar>
    </MetricLayout>
</template>

<style scoped lang="scss">
</style>