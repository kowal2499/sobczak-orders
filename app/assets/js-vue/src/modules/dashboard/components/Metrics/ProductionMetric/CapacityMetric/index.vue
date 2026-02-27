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
        }
    },
    computed: {
        perDepartmentData() {
            return this.aggregateByDepartment(this.data)
        },
        perDptAgreementData() {
            if (!this.activeDepartmentSlug) {
                return []
            }
            const dptKey = `involved_${this.activeDepartmentSlug}`
            return this.filteredInnerData
                .map(data => {
                    const {involved_dpt01, involved_dpt02, involved_dpt03, involved_dpt04, involved_dpt05, ...rest} = data
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
            return [
                {
                    data: this.perDepartmentData.map(({ value }) => value),
                    backgroundColor: bg,
                    borderColor: border,
                    borderWidth: 1
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
        }
    },
    data: () => ({
        showSidebar: false,
        activeDepartmentSlug: null,
    })
})

</script>

<template>
    <MetricLayout :is-busy="isBusy" class="border-left-success">
        <template #title>
            Obłożenie działów produkcji
        </template>

        <Chart
            :style="{height: '300px'}"
            :chartOptions="{
                responsive: true,
                maintainAspectRatio: false,
                legend: { display: false },
                scales: {
                    yAxes: [{
                      type: 'linear',
                      ticks: { min: 0, beginAtZero: true }
                    }]
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