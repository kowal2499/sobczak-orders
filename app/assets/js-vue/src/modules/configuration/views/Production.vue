<script>
import ProductionNavigation from "../components/production/ProductionNavigation.vue";
import CapacityForm from "../components/production/CapacityForm.vue";
import ScheduleHolidays from "@/modules/schedule/views/ScheduleHolidays.vue"

export default {
    name: "ProductionConfiguration",
    components: {
        ProductionNavigation,
        CapacityForm,
        ScheduleHolidays,
    },
}
</script>

<template>
    <SectionBlock class="section-gap">
        <ProductionNavigation>
            <template #working-days-calendar>
                <ScheduleHolidays />
            </template>
            <template #daily-capacity>
                <CapacityForm />
            </template>
        </ProductionNavigation>
    </SectionBlock>
</template>

<style scoped lang="scss">

</style>