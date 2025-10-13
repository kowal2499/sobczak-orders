<script lang="ts">
import {defineComponent} from 'vue'
import { mapState } from 'vuex'
import store from '@/store'
export default defineComponent({
    name: "App",

    computed: {
        ...mapState('ui', ['busyState', 'busyStateMessage'])
    }
})
</script>

<template>
    <div class="app-container">
        <div class="row" v-if="busyState">
            <div class="col-sm-12">
                <div class="card shadow mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            {{ busyStateMessage }}
                            <font-awesome-icon icon="spinner" spin />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <slot />
    </div>
</template>

<style scoped lang="scss">

</style>