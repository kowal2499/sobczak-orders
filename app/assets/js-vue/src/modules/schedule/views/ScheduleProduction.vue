<script>
import Calendar from '@/components/base/Calendar.vue'

export default {
    name: "ScheduleProduction",
    components: { Calendar },
    methods: {
        onDateSelected(dates) {
            console.log('onDateSelected', dates)
        }
    }


}
</script>

<template>
    <Calendar
        @date-selected="onDateSelected"
    />
</template>

<style scoped lang="scss">

</style>