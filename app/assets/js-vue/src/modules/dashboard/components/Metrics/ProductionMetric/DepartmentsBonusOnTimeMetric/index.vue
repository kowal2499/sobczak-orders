
<script>
import { defineComponent } from 'vue'
import MetricLayout from "../../MetricLayout.vue"
import Sidebar from '@/components/base/Sidebar.vue'
import BaseMetric from '../../BaseMetric.js'
import OnTimeDetails from '../components/OnTimeDetails.vue'
import SidebarNavbar from '@/components/layout/SidebarNavbar.vue'
import ProductionMetricMixin from '../ProductionMetricMixin'
import DepartmentMetricMixin from '../DepartmentMetricMixin'
import SidebarLayout from '@/components/layout/SidebarLayout.vue'
import fields from '../fields'

export default defineComponent({
    name: 'DepartmentsBonusOnTimeMetric',
    extends: BaseMetric,
    mixins: [ ProductionMetricMixin, DepartmentMetricMixin ],
    components: {
        MetricLayout, Sidebar, OnTimeDetails, SidebarNavbar, SidebarLayout,
    },

    props: {
        // zakres raportu (miesiąc wybrany na pulpicie) — potrzebny w popoverze "poza zakresem dat"
        dateStart: { type: String, default: null },
        dateEnd: { type: String, default: null },
        onRefresh: { type: Function, default: () => {} },
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
                    return;
                }
                this.innerData = this.mapDetails(this.data).map(item => this.addSearchKey(item));
            }
        }
    },

    computed: {
        perDepartmentData() {
            return this.aggregateByDepartment(this.data)
        },
    },

    methods: {
        beforeOpen() {
            this.q = null
        },
        onExportExcel() {
            return this.exportExcel(this.$t('dashboard.tasksCompletedOnTime'), fields, this.innerData)
        }
    }
})
</script>

<template>
    <MetricLayout :is-busy="isBusy" class="border-left-success">
        <template #title>
            {{ $t("dashboard.tasksCompletedOnTime") }}
        </template>

        <template #description>
            <p v-html="$t('dashboard.descriptions.tasksCompletedOnTime.p1')"></p>
            <p v-html="$t('dashboard.descriptions.tasksCompletedOnTime.p2')"></p>
        </template>

        <template #default>
            <Sidebar
                :title="$t('dashboard.tasksCompletedOnTime')"
                sidebar-class="size-100 size-lg-75"
            >
                <template #sidebar-action="{ open }">
                    <table class="table table-sm table-striped mt-2">
                        <tbody>
                            <tr v-for="department in perDepartmentData">
                                <td>{{ department.name }}</td>
                                <td class="text-right">
                                    <a href="#" @click.prevent="beforeOpen(); open()">
                                        {{ department.value | roundFloat }}
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </template>

                <template #sidebar-content="{ height }">
                    <SidebarLayout>
                        <template #header>
                            <SidebarNavbar
                                @search="q = $event"
                                @exportExcel="onExportExcel"
                            />
                        </template>
                        <template #content>
                            <OnTimeDetails
                                :data="filteredInnerData"
                                :height="height"
                                :report-range="{ start: dateStart, end: dateEnd }"
                                class="px-2 pb-2"
                                @saved="onRefresh()"
                            />
                        </template>
                    </SidebarLayout>
                </template>
            </Sidebar>
        </template>
    </MetricLayout>
</template>

<style scoped lang="scss">
    table {
        tbody {
            td {
                font-size: 0.85rem;
                padding: 0.3rem 0.3rem 0.3rem 0.1rem !important;
            }
        }
    }
</style>
