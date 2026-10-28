<template>
    <div id="app">
        <div id="wrapper">
            <AppSidebar />

            <button type="button" class="sidebar-burger" :aria-label="$t('app.menu.open')">
                <i class="fa fa-bars" aria-hidden="true"></i>
            </button>
            <div class="sidebar-backdrop"></div>

            <div id="content-wrapper" class="d-flex flex-column">
                <div id="content">
                    <Notifications :bag="[]" />

                    <div class="container-fluid">
                        <App>
                            <ErrorPage
                                v-if="!allowed"
                                :title="$t('app.title.forbidden')"
                                :message="$t('app.forbidden')"
                            />
                            <!-- Keyed by path so a param change (e.g. another order status) remounts the view, as a page load used to. -->
                            <router-view v-else :key="$route.path" />
                        </App>
                    </div>
                </div>
            </div>
        </div>

        <OrderPanelHost />
    </div>
</template>

<script>
import App from './App.vue'
import AppSidebar from '../components/AppSidebar.vue'
import ErrorPage from '../components/ErrorPage.vue'
import Notifications from '@/components/base/Notifications'
import OrderPanelHost from '@/modules/agreement/components/OrderPanelHost.vue'
import { isAllowed } from '@/router'

export default {
    name: 'AppLayout',
    components: { App, AppSidebar, ErrorPage, Notifications, OrderPanelHost },
    computed: {
        allowed() {
            return isAllowed(this.$route, this.$user)
        },
    },
}
</script>
