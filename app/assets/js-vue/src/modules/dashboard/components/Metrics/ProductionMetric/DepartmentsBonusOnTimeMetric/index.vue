
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
import OnTimeToleranceInput from '../components/OnTimeToleranceInput.vue'

export default defineComponent({
    name: 'DepartmentsBonusOnTimeMetric',
    extends: BaseMetric,
    mixins: [ ProductionMetricMixin, DepartmentMetricMixin ],
    components: {
        MetricLayout, Sidebar, OnTimeDetails, SidebarNavbar, SidebarLayout, OnTimeToleranceInput,
    },

    props: {
        // zakres raportu (miesiąc wybrany na pulpicie) - potrzebny w popoverze "poza zakresem dat"
        dateStart: { type: String, default: null },
        dateEnd: { type: String, default: null },
        onRefresh: { type: Function, default: () => {} },
        // widełki terminowości w dniach roboczych; null = wartość obowiązująca z backendu
        tolerance: { type: Number, default: null },
        onToleranceChange: { type: Function, default: () => {} },
    },

    computed: {
        innerData() {
            return Array.isArray(this.data)
                ? this.mapDetails(this.data).map(item => this.addSearchKey(item))
                : []
        },
        perDepartmentData() {
            return this.aggregateByDepartment(this.data)
        },
    },

    methods: {
        beforeOpen() {
            this.openings++
            this.q = null
        },
        onExportExcel() {
            return this.exportExcel(this.$t('dashboard.tasksCompletedOnTime'), fields, this.innerData)
        }
    },

    data: () => ({
        openings: 0,
    }),
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
            <p v-html="$t('dashboard.descriptions.tasksCompletedOnTime.p3')"></p>
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
                                    <b-skeleton v-if="!hasDepartmentData" width="2.5rem" class="ml-auto mb-0" />
                                    <a v-else href="#" @click.prevent="beforeOpen(); open()">
                                        {{ department.value | roundFloat }}
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <OnTimeToleranceInput
                        class="mt-1"
                        :value="tolerance"
                        @change="onToleranceChange"
                    />
                </template>

                <template #sidebar-header-actions>
                    <!-- Keyed per opening: the header outlives the lazy body, and beforeOpen clears the search. -->
                    <SidebarNavbar
                        :key="openings"
                        @search="q = $event"
                        @exportExcel="onExportExcel"
                    >
                        <template #controls>
                            <OnTimeToleranceInput
                                class="tolerance-row--toolbar"
                                :value="tolerance"
                                @change="onToleranceChange"
                            />
                        </template>
                    </SidebarNavbar>
                </template>

                <template #sidebar-content>
                    <SidebarLayout>
                        <template #content>
                            <OnTimeDetails
                                :data="filteredInnerData"
                                :report-range="{ start: dateStart, end: dateEnd }"
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
