
<script>
import { defineComponent } from 'vue'
import MetricLayout from "../MetricLayout.vue"
import Sidebar from '@/components/base/Sidebar.vue'
import BaseMetric from '../BaseMetric.js'
import { getUserDepartments } from '@/helpers'
import Details from '../OrdersCountMetric/Details.vue'

export default defineComponent({
    name: 'DepartmentsFactorMetric',
    extends: BaseMetric,

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
                        return acc + item.factor
                    }
                    return acc
                }, 0)
            }))
        },
        perAgreementData() {
            const agreementLinesMap = this.data?.reduce((acc, item) => {
                if (!acc.has(item.agreementLine.id)) {
                    acc.set(item.agreementLine.id, {
                        ...item.agreementLine,
                        ...item.agreement,
                        factor: item.factor,
                        customerName: item.customer.name,
                        completedAt: item.completedAt,
                        involved_dpt01: 0,
                        involved_dpt02: 0,
                        involved_dpt03: 0,
                        involved_dpt04: 0,
                        involved_dpt05: 0,
                    })
                }

                const lineData = acc.get(item.agreementLine.id)
                lineData[`involved_${item.departmentSlug}`] = 1

                return acc
            }, new Map())

            return [...agreementLinesMap.values()]


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