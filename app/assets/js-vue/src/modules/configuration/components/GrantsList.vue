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
                    <span class="module-header__title">{{ module.description }}</span>
                    <span class="module-header__namespace">{{ module.namespace }}</span>
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
.module-header {
    display: flex;
    align-items: baseline;
    gap: 0.5rem;
    margin-top: 1rem;
    padding-bottom: 0.35rem;
    border-bottom: 2px solid var(--colorRule);

    &__title {
        font-weight: 600;
        color: var(--colorPrimary);
    }

    &__namespace {
        font-size: 0.8rem;
        color: var(--colorInkMuted);
    }
}

.grant-item + .grant-item {
    border-top: 1px solid var(--colorRule);
}

</style>