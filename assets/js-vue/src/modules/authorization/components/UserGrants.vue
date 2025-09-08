<script>
import { defineComponent } from 'vue'
import proxyValue from '@/mixins/proxyValue'
import GrantsList from './GrantsList'

export default defineComponent({
    name: 'UserGrants',
    props: {
        userId: {
            type: Number,
            required: true
        },
    },
    mixins: [ proxyValue ],
    components: {
        GrantsList
    }
})
</script>

<template>
    <GrantsList />
</template>

<style scoped lang="scss">

</style>