<script>
import { defineComponent } from 'vue'
import MetricLayout from "./MetricLayout.vue"
import BaseMetric from './BaseMetric.js'
export default defineComponent({
    name: 'FactorsLimitMetric',
    extends: BaseMetric,
    components: {
        MetricLayout
    }
})
</script>

<template>
    <MetricLayout :is-busy="isBusy" class="border-left-primary">
        <template #title>
            {{ $t("dashboard.factorLimit") }}
        </template>

        <template #default>
            {{ data?.factorLimit }}
        </template>
    </MetricLayout>
</template>

<style scoped lang="scss">

</style>