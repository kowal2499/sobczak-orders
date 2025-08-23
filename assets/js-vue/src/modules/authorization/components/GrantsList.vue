<script>
import GrantItem from './GrantItem'

export default {
    name: 'GrantsList',
    props: {
        userId: {
            type: Number,
        },
        roleId: {
            type: Number
        },
        modules: {
            type: Array,
            default: () => ([])
        },
        grants: {
            type: Array,
            default: () => ([])
        }
    },

    components: {
        GrantItem
    },

    computed: {
        grantsInModule() {
            return this.modules.reduce((prev, current) => {
                prev[current.id] = this.grants.filter(grant => grant.module_id === current.id);
                return prev;
            }, {
            })
        }
    }
}
</script>

<template>
    <div>
        <div v-for="module in modules" :key="module.id">
            <div class="module-header">
                <h6>{{ module.namespace }}</h6>
                <span class="text-sm">{{ module.description }}</span>
            </div>
            <div class="py-2 d-flex flex-column gap-2">
                <div class="alert alert-light my-0 text-sm opacity-75" v-if="grantsInModule[module.id].length === 0">
                    {{ $t('auth.noGrantsInModule', { name: module.namespace }) }}
                </div>
                <template v-else>
                    <GrantItem
                        v-for="grant in grantsInModule[module.id]"
                        :grant="grant"
                        :key="grant.id"
                    />
                </template>
            </div>
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