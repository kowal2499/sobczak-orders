

<script>
import { defineComponent } from 'vue'
import MetricLayout from "./MetricLayout.vue"
import BaseMetric from './BaseMetric.js'
import { getUserDepartments } from '@/helpers'

export default defineComponent({
    name: 'DepartmentsFactorMetric',
    extends: BaseMetric,

    components: {
        MetricLayout
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
            <table class="table table-sm table-striped mt-2">
                <tbody>
                <tr v-for="department in perDepartmentData">
                    <td>{{ department.name }}</td>
                    <td class="text-right">
                        <a href="#" @click.prevent="() => {}">
                            {{ department.value | roundFloat }}
                        </a>
                    </td>
                </tr>
                </tbody>
            </table>
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