<template>
    <font-awesome-icon
        :icon="tickValue ? 'check-circle' : 'times-circle'"
        :class="tickValue ? 'text-success' : 'text-danger'"
    />
</template>

<script>
export default {
    name: "StatusIcon",
    props: {
        tickValue: Boolean
    }
}
</script>

<style scoped>

</style>