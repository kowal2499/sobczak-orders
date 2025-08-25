<script>
import GrantItem from './GrantItem'

export default {
    name: 'GrantsList',
    props: {
        modules: {
            type: Array,
            default: () => ([])
        },
        grants: {
            type: Array,
            default: () => ([])
        },
        store: {
            type: Array,
            default: () => ([])
        }
    },

    components: {
        GrantItem
    },

    methods: {
        getValueForGrant(grantId) {
            return this.store.filter(val => val.grantId === grantId)
        },
    },

    computed: {
        grantsInModule() {
            return this.modules.reduce((prev, current) => {
                prev[current.id] = this.grants.filter(grant => grant.module_id === current.id);
                return prev;
            }, {
            })
        }
    },
}
</script>

<template>
    <div>
        <div v-for="module in modules" :key="module.id">
            <template v-if="grantsInModule[module.id].length">
                <div class="module-header">
                    <h6>{{ module.namespace }}</h6>
                    <span class="text-sm">{{ module.description }}</span>
                </div>
                <div class="py-2 d-flex flex-column gap-2">
                    <GrantItem
                        v-for="grant in grantsInModule[module.id]"
                        :grant="grant"
                        :value="getValueForGrant(grant.id)"
                        @input="$emit('grantChanged', $event)"
                        :key="grant.id"
                    />
                </div>
            </template>
        </div>
    </div>
</template>

<style scoped lang="scss">
@import "~/css/helper/variables";

.module-header {
    width: 100%;
    padding: 2rem 0;
    background-color: $lightgray3;
    border: 1px solid $lightgray;
    text-align: center;

    h6 {
        font-weight: normal;
        text-transform: uppercase;
        margin: 0;
    }
}
</style>