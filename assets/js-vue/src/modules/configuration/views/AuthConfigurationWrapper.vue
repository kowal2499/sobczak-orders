<script>
import {defineComponent} from 'vue'
import * as TYPES from '@/store/types'
import store from '@/store'

export default defineComponent({
    name: "AuthWrapper",

    async beforeCreate() {
        await store.dispatch('ui/' + TYPES.ACTION_ENABLE_BUSY_STATE, 'Inicjalizacja modułu konfiguracji użytkowników i ról...')
        await Promise.all([
            store.dispatch('auth/' + TYPES.ACTION_AUTH_FETCH_MODULES),
            store.dispatch('auth/' + TYPES.ACTION_AUTH_FETCH_GRANTS),
            store.dispatch('auth/' + TYPES.ACTION_AUTH_FETCH_ROLES),``
        ])
        await store.dispatch('ui/' + TYPES.ACTION_DISABLE_BUSY_STATE)
        this.isInitialized = true
    },

    data: () => ({
        isInitialized: false
    })
})
</script>

<template>
    <div v-if="isInitialized">
        <slot />
    </div>
</template>

<style scoped>

</style>