<script>
import CollapsibleCard from '@/components/base/CollapsibleCard'

export default {
    name: "CapacityHistory",
    components: { CollapsibleCard },
    props: {
        capacityHistory: {
            type: Array,
            default: () => []
        },
        isBusy: {
            type: Boolean,
            default: false
        }
    },
    computed: {
        canDelete() {
            return this.$user.can('work-configuration.capacity')
        }
    },
    methods: {
        onDelete(id) {
            this.$bvModal.msgBoxConfirm(this.$t('_deleteConfirmation'), {
                title: this.$t('_confirmation'),
                okVariant: 'danger',
                okTitle: this.$t('delete'),
                cancelVariant: 'outline-secondary',
                cancelTitle: this.$t('cancel'),
                centered: true,
            })
                .then(value => {
                    if (!value) {
                        return
                    }
                    this.$emit('delete', id)
                })
        }
    }
}
</script>

<template>
    <CollapsibleCard :title="$t('config.production.capacityHistoryTitle')">
        <div class="text-center" v-if="isBusy">
            <font-awesome-icon :icon="['fas', 'spinner']" spin />
        </div>
        <p class="text-muted mb-0" v-else-if="capacityHistory.length === 0">
            {{ $t('config.production.capacityHistoryEmpty') }}
        </p>
        <table class="table table-sm mb-0" v-else>
            <thead>
                <tr>
                    <th>{{ $t('config.production.dateFrom') }}</th>
                    <th class="text-right">{{ $t('config.production.capacityTitle') }}</th>
                    <th v-if="canDelete"></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="item in capacityHistory" :key="item.id">
                    <td class="align-middle">{{ item.dateFrom }}</td>
                    <td class="align-middle text-right numeric">
                        {{ parseFloat(item.capacity).toFixed(2) }}
                    </td>
                    <td v-if="canDelete" class="align-middle text-right">
                        <button
                            type="button"
                            class="btn btn-outline-danger btn-sm"
                            :aria-label="$t('delete')"
                            :title="$t('delete')"
                            @click="onDelete(item.id)"
                        >
                            <font-awesome-icon :icon="['fas', 'trash']" />
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </CollapsibleCard>
</template>

<style scoped lang="scss">
.numeric {
    font-variant-numeric: tabular-nums;
}
</style>