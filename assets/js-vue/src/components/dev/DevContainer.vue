<script>
import { defineComponent } from 'vue'

export default defineComponent({
    name: 'DevContainer',
    computed: {
        component() {
            return () => import('./components/StartProductionActionDev.vue')
        }
    }
})
</script>

<template>
    <div>
        <component :is="component" />
    </div>
</template>

<style>

</style>