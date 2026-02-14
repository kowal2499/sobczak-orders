<script>
import GrantItem from './GrantItem'
import { mapGetters } from 'vuex'

export default {
    name: 'GrantsList',
    props: {},

    components: {
        GrantItem
    },

    computed: {
        ...mapGetters('auth', ['allGrants', 'allModules']),

        grantsInModule() {
            return this.allModules.reduce((prev, current) => {
                prev[current.id] = this.allGrants.filter(grant => grant.module_id === current.id);
                return prev;
            }, {
            })
        }
    },

    data: () => ({
        isBusy: false,
    })
}
</script>

<template>
    <div>
        <div v-for="module in allModules" :key="module.id">
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
                        <div class="row">
                            <div class="col-6">
                                <h6 class="font-weight-bolder">{{ grant.name }}</h6>
                                <div class="text-sm text-muted">{{ grant.description }}</div>
                                <div style="font-family: 'Courier New'; font-size: 0.8em" class="mt-2 text-muted">
                                    {{ grant.slug }}
                                </div>
                            </div>
                            <div class="col-6">
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
//@import "~/css/helper/variables";
@use "css/helper/variables" as *;

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