
<script>
import { defineComponent } from 'vue'
import MetricLayout from "../MetricLayout.vue"
import Sidebar from '@/components/base/Sidebar.vue'
import BaseMetric from '../BaseMetric.js'
import Details from './components/Details.vue'
import ProductionMetricMixin from './ProductionMetricMixin'
import DepartmentMetricMixin from './DepartmentMetricMixin'

export default defineComponent({
    name: 'DepartmentsFactorMetric',
    extends: BaseMetric,
    mixins: [ProductionMetricMixin, DepartmentMetricMixin],
    components: {
        MetricLayout, Sidebar, Details,
    },

    computed: {
        perDepartmentData() {
            return this.aggregateByDepartment(this.data)
        },
        perAgreementData() {
            return this.mapDetails(this.data)
        }
    },

    methods: {
        calcDetails(callback) {
            // Placeholder for future detail calculation logic
            callback();
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
                                    <a href="#" @click.prevent="calcDetails(open)">
                                        {{ department.value | roundFloat }}
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </template>

                <template #sidebar-content="{ height }">
                    <Details :data="perAgreementData" :height="height" class="px-2 pb-2" />
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