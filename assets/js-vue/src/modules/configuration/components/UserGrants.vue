<script>
import {defineComponent} from 'vue'
import proxyValue from '@/mixins/proxyValue'
import GrantsList from './GrantsList'
import GrantUserValue from './GrantUserValue'
import {mapGetters} from 'vuex'

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
        GrantsList,
        GrantUserValue,
    },

    mounted() {
        this.valuesPerGrant = this.allGrants.reduce((prev, current) => {
            prev[current.id] = this.proxyData.filter(item => item.grant_id === current.id)
            return prev
        }, {})
    },

    computed: {
        ...mapGetters('auth', ['allGrants', 'allModules']),
    },

    methods: {
        setValuesPerGrant(grantId, grantsValue) {
            this.valuesPerGrant = {
                ...this.valuesPerGrant,
                [grantId]: grantsValue
            }
            this.proxyData = Object.values(this.valuesPerGrant).flat()
        }
    },

    data: () => ({
        valuesPerGrant: {}
    })
})

</script>

<template>
    <GrantsList #grantValue="{ grant }">
        <GrantUserValue
            :user-id="userId"
            :grant="grant"
            :value="valuesPerGrant[grant.id]"
            @input="value => setValuesPerGrant(grant.id, value)"
        />
    </GrantsList>
</template>

<style scoped lang="scss">

</style>