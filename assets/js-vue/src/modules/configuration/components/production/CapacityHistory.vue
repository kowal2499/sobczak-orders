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
                buttonSize: 'sm',
                okVariant: 'danger',
                okTitle: this.$t('delete'),
                cancelTitle: this.$t('cancel'),
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
    <CollapsibleCard title="Historia zmian">
        <div class="text-center" v-if="isBusy">
            <font-awesome-icon :icon="['fas', 'spinner']" spin class="mr-1" />
        </div>
        <div class="alert alert-warning" v-else-if="capacityHistory.length === 0">
            {{ $t('config.production.capacityHistoryEmpty') }}
        </div>
        <table class="table table-sm" v-else>
            <thead>
                <tr>
                    <th>{{ $t('config.production.dateFrom') }}</th>
                    <th>{{ $t('config.production.capacityTitle') }}</th>
                    <th v-if="canDelete">{{ $t('delete') }}</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="item in capacityHistory" :key="item.id">
                    <td>{{ item.dateFrom }}</td>
                    <td>
                        {{ parseFloat(item.capacity).toFixed(2) }}
                    </td>
                    <td v-if="canDelete">
                        <button class="btn btn-sm" @click="onDelete(item.id)">
                            <font-awesome-icon :icon="['fas', 'trash']" class="text-danger" />
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </CollapsibleCard>
</template>

<style scoped lang="scss">

</style>