
<script>
import { defineComponent } from 'vue'
import MetricLayout from "../MetricLayout.vue"
import Sidebar from '@/components/base/Sidebar.vue'
import BaseMetric from '../BaseMetric.js'
import { getUserDepartments } from '@/helpers'
import Details from './components/Details.vue'
import ProductionMetricMixin from '../ProductionMetric/ProductionMetricMixin'
export default defineComponent({
    name: 'DepartmentsFactorMetric',
    extends: BaseMetric,
    mixins: [ProductionMetricMixin],
    components: {
        MetricLayout, Sidebar, Details,
    },

    computed: {
        perDepartmentData() {
            return getUserDepartments().map((department) => ({
                name: department.name,
                slug: department.slug,
                value: this.data?.reduce((acc, item) => {
                    if (item.departmentSlug === department.slug) {
                        return acc + item.factors.factor
                    }
                    return acc
                }, 0)
            }))
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