// Only what the remaining server-rendered Twig pages (products, customers) mount; SPA views come from the router.
import Notifications from './base/Notifications';
import MenuItem from './base/MenuItem';
import NavOrders from './navigation/NavOrders';
import ContextMenu from './base/Menu/ContextMenu.vue'

export default {
    Notifications,
    MenuItem,
    NavOrders,
    ContextMenu,
}
