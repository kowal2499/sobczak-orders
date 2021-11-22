<template>
    <base-badge :metric="metric">
        <template #title>
            {{ $t(metric.title) }}
        </template>

        <table class="table table-sm table-striped mt-2">
            <tbody>
                <tr v-for="department in metric.value.perDepartment">
                    <td>{{ department.name }}</td>
                    <td class="text-right">
                        <a href="#" @click.prevent="$emit('clicked', metric.id)">
                            {{ department.factorsSummary | roundFloat }}
                        </a>
                    </td>
                </tr>
            </tbody>
        </table>
    </base-badge>
</template>

<script>
import BaseBadge from "./BaseBadge";
export default {
    name: "TasksBadge",
    components: {BaseBadge},
    props: {
        metric: {
            type: Object,
            required: true
        },
    }
}
</script>

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