<template>
    <Sidebar
        v-if="shownId"
        :key="shownId"
        v-model="open"
        :level="0"
        :title="$t('editOrder')"
        sidebar-class="size-100 size-lg-75"
        @closed="onClosed"
    >
        <template #sidebar-content>
            <div class="order-edit">
                <AgreementForm
                    :agreement-id="shownId"
                    @saved="onSaved"
                    @cancel="open = false"
                />
            </div>
        </template>
    </Sidebar>
</template>

<script>
import Sidebar from '@/components/base/Sidebar.vue'
import AgreementForm from '../view/AgreementForm.vue'
import { closeOrderPanel, orderEditOf } from '../orderPanel'

// Order edit drawer driven by ?editOrder= in the current route, the same way OrderPanelHost handles ?panel=.
export default {
    name: 'OrderEditHost',
    components: { Sidebar, AgreementForm },
    data: () => ({
        shownId: null,
        open: false,
        saved: false,
        openedInApp: false,
    }),
    computed: {
        agreementId() {
            return orderEditOf(this.$route)
        },
    },
    watch: {
        $route(to, from) {
            if (orderEditOf(to) && !orderEditOf(from) && to.path === from.path) {
                this.openedInApp = true
            }
        },
        agreementId: {
            handler(id) {
                if (!id) {
                    this.open = false
                    return
                }
                if (this.shownId === id) {
                    this.open = true
                    return
                }
                this.shownId = id
                this.saved = false
                this.open = false
                this.$nextTick(() => this.open = true)
            },
            immediate: true,
        },
    },
    methods: {
        onSaved() {
            this.saved = true
            this.open = false
        },
        onClosed() {
            const shownId = this.shownId
            const saved = this.saved

            if (this.agreementId === shownId) {
                if (this.openedInApp) {
                    this.$router.back()
                } else {
                    closeOrderPanel(this.$router)
                }
            }

            this.openedInApp = false
            this.shownId = null
            this.saved = false

            if (saved) {
                window.EventBus.$emit('agreementLineSaved')
            }
        },
    },
}
</script>

<style scoped>
.order-edit {
    padding: 0 1.25rem 1.25rem;
}
</style>
