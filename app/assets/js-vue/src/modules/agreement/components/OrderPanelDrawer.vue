<template>
    <Sidebar
        v-model="internalOpen"
        :level="0"
        :title="$t('_agreement_line_panel')"
        sidebar-class="size-100 size-lg-75"
        :before-close="beforeClose"
        @closed="onClosed"
    >
        <template v-if="canEdit" #sidebar-header-actions>
            <button
                type="button"
                class="btn btn-primary btn-sm d-none d-sm-inline-block"
                :disabled="busy"
                @click="save"
            >
                <font-awesome-icon v-if="saving" icon="spinner" spin class="mr-1" />{{ $t('orders.saveChanges') }}
            </button>
        </template>

        <template #sidebar-content>
            <div class="order-panel">
                <single-order
                    ref="singleOrder"
                    :key="lineId"
                    :line-id="lineId"
                    :task-statuses="taskStatuses"
                    :active-department="activeDepartment"
                    @saved="onSaved"
                    @dirty-change="onDirtyChange"
                    @busy-change="busy = $event"
                    @saving-change="saving = $event"
                />
            </div>
        </template>
    </Sidebar>
</template>

<script>
import Sidebar from '@/components/base/Sidebar.vue'
import SingleOrder from '@/components/orders/single/SingleOrder'
import { agreementStatusesMap } from '@/helpers'

export default {
    name: 'OrderPanelDrawer',
    components: { Sidebar, SingleOrder },

    provide: {
        parentSidebarLevel: 0,
    },

    model: {
        prop: 'value',
        event: 'input',
    },

    props: {
        value: { type: Boolean, default: false },
        lineId: { type: Number, required: true },
        activeDepartment: { type: String, default: null },
    },

    data: () => ({
        internalOpen: false,
        isDirty: false,
        busy: true,
        saving: false,
    }),

    computed: {
        canEdit() {
            return this.$user.can(this.$privilages.CAN_PRODUCTION)
        },
        taskStatuses() {
            return Object.fromEntries(
                Object.entries(agreementStatusesMap).map(([code, s]) => [code, s.name])
            )
        }
    },

    watch: {
        value: {
            handler(val) {
                this.internalOpen = val
            },
            immediate: true,
        },
        internalOpen(val) {
            this.$emit('input', val)
        }
    },

    methods: {
        async beforeClose() {
            return this.confirmLeave()
        },

        // Also used by OrderPanelHost's router guard, so a back button or a menu click asks too.
        async confirmLeave() {
            if (!this.isDirty) {
                return true
            }
            if (!(await this.confirmDiscard())) {
                return false
            }
            this.$refs.singleOrder?.revert()
            this.isDirty = false
            return true
        },

        onClosed() {
            this.$emit('closed')
        },

        save() {
            this.$refs.singleOrder?.save()
        },

        onDirtyChange(val) {
            this.isDirty = val
        },

        onSaved() {
            this.isDirty = false
            this.$emit('saved')
            this.internalOpen = false
        },

        confirmDiscard() {
            return this.$bvModal.msgBoxConfirm(this.$t('_unsavedChangesConfirm'), {
                title: this.$t('_confirmation_required'),
                size: 'md',
                okTitle: this.$t('_discardChanges'),
                okVariant: 'danger',
                cancelTitle: this.$t('_continueEditing'),
                cancelVariant: 'secondary',
                centered: true,
            })
        }
    }
}
</script>

<style scoped>
.order-panel {
    padding: 0 1.25rem 1.25rem;
}
</style>
