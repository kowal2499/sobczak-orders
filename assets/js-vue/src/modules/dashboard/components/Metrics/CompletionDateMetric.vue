<script>
import { defineComponent } from 'vue'
import MetricLayout from "./MetricLayout.vue"
import BaseMetric from './BaseMetric.js'
export default defineComponent({
    name: 'CompletionDateMetric',
    extends: BaseMetric,
    components: {
        MetricLayout
    }
})
</script>

<template>
    <MetricLayout :is-busy="isBusy" class="border-left-warning">
        <template #title>
            {{ $t("dashboard.firstFreeDay") }}
        </template>

        <template #default>
            {{ data?.firstFreeDay }}
        </template>
    </MetricLayout>
</template>

<style scoped lang="scss">

</style>