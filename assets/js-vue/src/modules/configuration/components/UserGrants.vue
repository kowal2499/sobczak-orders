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
        grantsPerRole: {
            type: Array,
            default: () => []
        }
    },
    mixins: [ proxyValue ],

    components: {
        GrantsList,
        GrantUserValue,
    },

    computed: {
        ...mapGetters('auth', ['allGrants', 'allModules']),
    },

    mounted() {
        this.valuesPerGrant = this.allGrants.reduce((prev, current) => {
            prev[current.id] = this.proxyData.filter(item => item.grant_id === current.id)
            return prev
        }, {})
    },

    watch: {
        proxyData: {
            deep: true,
            immediate: true,
            handler() {
                this.valuesPerGrant = this.allGrants.reduce((prev, current) => {
                    prev[current.id] = this.proxyData.filter(item => item.grant_id === current.id)
                    return prev
                }, {})
            }
        },
        grantsPerRole: {
            deep: true,
            immediate: true,
            handler() {
                this.valuesPerRole = this.allGrants.reduce((prev, current) => {
                    prev[current.id] = this.grantsPerRole.filter(item => item.grant_id === current.id)
                    return prev
                }, {})
            }
        }
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
        valuesPerGrant: {},
        valuesPerRole: {}, // read only
    })
})

</script>

<template>
    <GrantsList #grantValue="{ grant }">
        <GrantUserValue
            :user-id="userId"
            :grant="grant"
            :value="valuesPerGrant[grant.id]"
            :value-per-role="valuesPerRole[grant.id]"
            @input="value => setValuesPerGrant(grant.id, value)"
        />
    </GrantsList>
</template>

<style scoped lang="scss">

</style>