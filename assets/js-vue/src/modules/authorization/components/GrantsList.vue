<script>
import GrantItem from './GrantItem'
import {
    fetchGrants,
    fetchModules,
} from '../repository/authorizatonRepository'

export default {
    name: 'GrantsList',
    props: {},

    components: {
        GrantItem
    },

    mounted() {
        this.reset()
    },

    methods: {
        async reset() {
            this.isBusy = true
            const [modulesData, grantsData] = await Promise.all([
                fetchModules(),
                fetchGrants(),
            ])

            this.modules = modulesData.data
            this.grants = grantsData.data
        }
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

    data: () => ({
        modules: [],
        grants: [],
        isBusy: false,
    })
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
                    <div v-for="grant in grantsInModule[module.id]"
                         class="grant-item"
                         :key="grant.id"
                    >
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="my-1">
                                <h6 class="font-weight-bolder">{{ grant.name }}</h6>
                                <div class="text-sm text-muted">{{ grant.description }}</div>
                                <div style="font-family: 'Courier New'; font-size: 0.8em" class="mt-2 text-muted">
                                    {{ grant.slug }}
                                </div>
                            </div>
                            <div>
                                <slot name="grantValue" :grant="grant">
                                    <div class="text-sm text-muted">[Brak slotu grantValue]</div>
                                </slot>
                            </div>
                        </div>
                    </div>
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
.grant-item + .grant-item {
    border-top: 1px solid #e9ecef;
}

</style>