<template>
    <nav class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion">

        <div class="sidebar__brand text-center">
            <img class="img-fluid sidebar__logo-full" src="/imgs/logo-sobczak.png" alt="Sobczak">
            <img class="sidebar__logo-mark" src="/imgs/logo-sobczak-sm.png" alt="Sobczak">
        </div>

        <ul class="sidebar__nav">
            <template v-for="(item, i) in items">
                <hr v-if="item.divider" :key="`divider-${i}`" class="sidebar-divider">

                <MenuItem
                    v-else-if="item.key === 'orders'"
                    :key="item.key"
                    :active="isGroupActive(item)"
                    title-icon="fa fa-address-book-o"
                    :title="$t(`app.${item.label}`)"
                >
                    <NavOrders :elements="orderElements(item)" />
                </MenuItem>

                <MenuItem
                    v-else-if="item.children"
                    :key="item.key"
                    :active="isGroupActive(item)"
                    :title-icon="item.icon"
                    :title="$t(`app.${item.label}`)"
                >
                    <div class="submenu collapse-inner">
                        <router-link
                            v-for="child in item.children"
                            :key="child.label"
                            :to="child.to"
                            :class="{ active: child.isActive($route) }"
                        >
                            {{ $t(`app.${child.label}`) }}
                        </router-link>
                    </div>
                </MenuItem>

                <li v-else :key="item.label" class="nav-item" :class="{ active: item.isActive($route) }">
                    <router-link :to="item.to" class="nav-link">
                        <i :class="item.icon" aria-hidden="true"></i>
                        <span class="sidebar__label">{{ $t(`app.${item.label}`) }}</span>
                    </router-link>
                </li>
            </template>
        </ul>

        <div class="sidebar__footer">
            <hr class="sidebar-divider">
            <LanguageSwitcher />
            <ContextMenu />
        </div>
    </nav>
</template>

<script>
import MenuItem from '@/components/base/MenuItem.vue'
import NavOrders from '@/components/navigation/NavOrders.vue'
import ContextMenu from '@/components/base/Menu/ContextMenu.vue'
import LanguageSwitcher from './LanguageSwitcher.vue'
import { buildMenu } from '../navigation/menu'

export default {
    name: 'AppSidebar',
    components: { MenuItem, NavOrders, ContextMenu, LanguageSwitcher },
    watch: {
        // The clicked link keeps focus without a page load, and :focus-within would hold the sidebar open.
        $route() {
            if (this.$el.contains(document.activeElement)) {
                document.activeElement.blur()
            }
        },
    },
    computed: {
        items() {
            return buildMenu(this.$user)
        },
    },
    methods: {
        isGroupActive(item) {
            return item.activeOn.includes(this.$route.name)
        },
        orderElements(item) {
            return item.children.map(child => ({
                title: this.$t(`app.${child.label}`),
                statusId: child.statusId,
                to: child.to,
                active: child.isActive(this.$route),
            }))
        },
    },
}
</script>
