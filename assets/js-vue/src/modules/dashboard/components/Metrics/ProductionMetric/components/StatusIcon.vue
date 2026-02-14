<template>
    <font-awesome-icon
        :icon="tickValue ? 'check-circle' : 'times'"
        :class="tickValue ? 'text-success' : 'text-danger opacity-75'"
        style="font-size: 20px"
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