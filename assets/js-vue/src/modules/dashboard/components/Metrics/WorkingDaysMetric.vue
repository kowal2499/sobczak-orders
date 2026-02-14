<script>
import { defineComponent } from 'vue'
import MetricLayout from "./MetricLayout.vue"
import BaseMetric from './BaseMetric.js'
export default defineComponent({
    name: 'WorkingDaysMetric',
    extends: BaseMetric,
    components: {
        MetricLayout
    }
})
</script>

<template>
    <MetricLayout :is-busy="isBusy" class="border-left-primary">
        <template #title>
            {{ $t("dashboard.workingDays") }}
        </template>

        <template #default>
            {{ data?.workingDays }}
        </template>
    </MetricLayout>
</template>

<style scoped lang="scss">

</style>