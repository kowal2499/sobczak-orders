<template>
    <div class="alert alert-info text-center">
        <i class="fa fa-spinner fa-spin fa-1x"></i>
        {{ description }}
    </div>
</template>

<script>
    export default {
        name: "Waiting",

        props: [ 'description']
    }
</script>

<style scoped>

</style>