
<script>
import { defineComponent } from 'vue'
import MetricLayout from "../../MetricLayout.vue"
import Sidebar from '@/components/base/Sidebar.vue'
import BaseMetric from '../../BaseMetric.js'
import Details from '../components/Details.vue'
import DetailsNavbar from '../components/DetailsNavbar.vue'
import ProductionMetricMixin from '../ProductionMetricMixin'
import DepartmentMetricMixin from '../DepartmentMetricMixin'
import SidebarLayout from '../../SidebarLayout.vue'
import fields from '../fields'

export default defineComponent({
    name: 'DepartmentsFactorMetric',
    extends: BaseMetric,
    mixins: [ ProductionMetricMixin, DepartmentMetricMixin ],
    components: {
        MetricLayout, Sidebar, Details, DetailsNavbar, SidebarLayout,
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
            return this.exportExcel(this.$t('dashboard.tasksCompleted'), fields, this.innerData)
        }
    }
})
</script>

<template>
    <MetricLayout :is-busy="isBusy" class="border-left-success">
        <template #title>
            {{ $t("dashboard.tasksCompleted") }}
        </template>

        <template #default>
            <Sidebar
                :title="$t('dashboard.tasksCompleted')"
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
                            <DetailsNavbar
                                @search="q = $event"
                                @exportExcel="onExportExcel"
                            />
                        </template>
                        <template #content>
                            <Details :data="filteredInnerData" :height="height" class="px-2 pb-2" />
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