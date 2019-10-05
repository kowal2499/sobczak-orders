<template>
    <div>

        <div class="submenu mx-2 collapse-inner" v-for="element in elements">
            <a :href="element.path" :class="{active: element.active}">
                {{ element.title }}
            </a>
        </div>

    </div>
</template>

<script>
    export default {
        name: 'NavOrders',

        props: ['elements']
    }
</script>

<style scoped>

</style>