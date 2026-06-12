<template>
    <Sidebar
        v-model="internalOpen"
        :level="0"
        :title="$t('_agreement_line_panel')"
        sidebar-class="size-100 size-lg-75"
        :before-close="beforeClose"
        @closed="onClosed"
    >
        <template #sidebar-content>
            <div class="p-3">
                <single-order
                    ref="singleOrder"
                    :key="lineId"
                    :line-id="lineId"
                    :task-statuses="taskStatuses"
                    :active-department="activeDepartment"
                    @saved="onSaved"
                    @dirty-change="onDirtyChange"
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
        pushedHistory: false,
        isDirty: false,
    }),

    computed: {
        taskStatuses() {
            return Object.fromEntries(
                Object.entries(agreementStatusesMap).map(([code, s]) => [code, s.name])
            )
        }
    },

    watch: {
        value(val) {
            if (val) {
                this.openDrawer()
            } else {
                this.internalOpen = false
            }
        },
        internalOpen(val) {
            this.$emit('input', val)
        }
    },

    mounted() {
        window.addEventListener('popstate', this.handlePopstate)
    },

    beforeDestroy() {
        window.removeEventListener('popstate', this.handlePopstate)
    },

    methods: {
        openDrawer() {
            const targetPath = `/agreement/line/${this.lineId}`
            this.internalOpen = true

            if (window.location.pathname === targetPath) {
                return
            }

            if (this.pushedHistory) {
                history.replaceState({ orderPanel: this.lineId }, '', targetPath)
            } else {
                history.pushState({ orderPanel: this.lineId }, '', targetPath)
                this.pushedHistory = true
            }
        },

        async beforeClose() {
            if (this.isDirty) {
                const confirmed = await this.confirmDiscard()
                if (!confirmed) return false
                this.$refs.singleOrder?.revert()
                this.isDirty = false
            }
            return true
        },

        onClosed() {
            if (this.pushedHistory) {
                this.pushedHistory = false
                history.back()
            }
        },

        async handlePopstate(event) {
            if (!this.internalOpen) {
                return
            }
            if (event.state && event.state.orderPanel) {
                return
            }

            if (this.isDirty) {
                const confirmed = await this.confirmDiscard()
                if (!confirmed) {
                    history.pushState({ orderPanel: this.lineId }, '', `/agreement/line/${this.lineId}`)
                    this.pushedHistory = true
                    return
                }
                this.$refs.singleOrder?.revert()
                this.isDirty = false
            }
            this.pushedHistory = false
            this.internalOpen = false
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
