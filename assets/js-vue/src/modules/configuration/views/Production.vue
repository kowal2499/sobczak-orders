<script>
import CollapsibleCard from '@/components/base/CollapsibleCard'
import ProductionNavigation from "../components/production/ProductionNavigation.vue";
import CapacityForm from "../components/production/CapacityForm.vue";
import CapacityHistory from "../components/production/CapacityHistory.vue";
import WorkScheduleCalendar from "../components/production/WorkScheduleCalendar.vue";

export default {
    name: "ProductionConfiguration",
    components: {
        CollapsibleCard,
        ProductionNavigation,
        CapacityForm,
        CapacityHistory,
        WorkScheduleCalendar,
    },
}
</script>

<template>
    <CollapsibleCard :title="$t('config.production.title')" no-padding locked>
        <ProductionNavigation>
            <template #working-days-calendar>
                <WorkScheduleCalendar />
            </template>
            <template #daily-capacity>
                <CapacityForm />
            </template>
        </ProductionNavigation>
    </CollapsibleCard>
</template>

<style scoped lang="scss">

</style>