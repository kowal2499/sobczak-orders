import NewOrder from './orders/NewOrder';
import OrdersList from './orders/list/OrdersList';
import ProductionList from './production/ProductionList';
import AgreementLineListRM from '../modules/agreementLineRM/views/AgreementLineListRM';
import SingleOrder from './orders/single/SingleOrder';
// import Dashboard from './dashboard/Dashboard';
import UsersList from './users/UsersList';
import Notifications from './base/Notifications';
import Dropdown from './base/Dropdown';

import MenuItem from './base/MenuItem';
import NavOrders from './navigation/NavOrders';

import TagsModule from "../modules/tags";
import ConfigurationModuleWrapper from '../modules/configuration/views/ConfigurationModuleWrapper.vue'

import UserSingle from '../modules/configuration/views/UserSingle.vue'
import Roles from "../modules/configuration/views/Roles.vue"

import ContextMenu from './base/Menu/ContextMenu.vue'

import App from '../modules/app/views/App.vue'
import DevContainer from './dev/DevContainer'

export default {
    App,
    NewOrder,
    OrdersList,
    ProductionList,
    AgreementLineListRM,
    SingleOrder,
    // Dashboard,
    UsersList,
    UserSingle,
    Roles,
    Dropdown,
    MenuItem,
    Notifications,
    NavOrders,
    Dashboard: () => import('../modules/dashboard/Dashboard.vue'),
    ConfigurationModuleWrapper,
    ...TagsModule,
    ContextMenu,
    DevContainer,
}
