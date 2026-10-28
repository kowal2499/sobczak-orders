<template>
    <OrderPanelDrawer
        v-if="shown && $user.can('production.panel')"
        ref="drawer"
        :key="shown.lineId"
        v-model="open"
        :line-id="shown.lineId"
        :active-department="shown.department"
        @saved="saved = true"
        @closed="onClosed"
    />
</template>

<script>
import OrderPanelDrawer from './OrderPanelDrawer.vue'
import { closeOrderPanel, panelOf } from '../orderPanel'

/**
 * The single order panel drawer of the app, driven by ?panel= in the current route.
 * Openers only change the URL (openOrderPanel); data owners refresh on 'agreementLineSaved'.
 */
export default {
    name: 'OrderPanelHost',
    components: { OrderPanelDrawer },
    data: () => ({
        shown: null,
        open: false,
        saved: false,
        // Opened by a click in the app, so closing can step back instead of stacking another history entry.
        openedInApp: false,
    }),
    computed: {
        panel() {
            return panelOf(this.$route)
        },
    },
    watch: {
        $route(to, from) {
            if (panelOf(to) && !panelOf(from) && to.path === from.path) {
                this.openedInApp = true
            }
        },
        panel: {
            handler(panel) {
                if (!panel) {
                    this.open = false
                    return
                }
                if (this.shown && this.shown.lineId === panel.lineId) {
                    this.open = true
                    return
                }
                this.shown = panel
                this.saved = false
                this.open = false
                // Mounted closed first, so the drawer slides in instead of appearing.
                this.$nextTick(() => this.open = true)
            },
            immediate: true,
        },
    },
    created() {
        this.removeGuard = this.$router.beforeEach(async (to, from, next) => {
            const leaving = panelOf(from)
            const staying = panelOf(to)
            if (!leaving || (staying && staying.lineId === leaving.lineId && to.path === from.path)) {
                return next()
            }
            const drawer = this.$refs.drawer
            next(!drawer || await drawer.confirmLeave())
        })
    },
    beforeDestroy() {
        this.removeGuard()
    },
    methods: {
        onClosed() {
            const lineId = this.shown.lineId
            const saved = this.saved

            // Closed by its own button or after a save: the URL still points at the panel.
            if (this.panel && this.panel.lineId === lineId) {
                if (this.openedInApp) {
                    this.$router.back()
                } else {
                    closeOrderPanel(this.$router)
                }
            }

            this.openedInApp = false
            this.shown = null
            this.saved = false

            // Only after the drawer is gone: a reload may rebuild the row that opened it.
            if (saved) {
                window.EventBus.$emit('agreementLineSaved', lineId)
            }
        },
    },
}
</script>
