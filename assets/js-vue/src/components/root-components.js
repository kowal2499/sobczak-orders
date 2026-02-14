import NewOrder from './orders/NewOrder';
import OrdersList from './orders/list/OrdersList';
import ProductionList from './production/ProductionList';
import AgreementLineListRM from '../modules/agreementLineRM/views/AgreementLineListRM';
import SingleOrder from './orders/single/SingleOrder';
import UsersList from './users/UsersList';
import Notifications from './base/Notifications';
import Dropdown from './base/Dropdown';

import MenuItem from './base/MenuItem';
import NavOrders from './navigation/NavOrders';

import TagsModule from "../modules/tags";

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
    UsersList,
    Dropdown,
    MenuItem,
    Notifications,
    NavOrders,
    Dashboard: () => import('../modules/dashboard/Dashboard.vue'),

    AuthConfigurationWrapper: () => import('../modules/configuration/views/AuthConfigurationWrapper.vue'),
    UserSingle: () => import('../modules/configuration/views/UserSingle.vue'),
    Roles: () => import('../modules/configuration/views/Roles.vue'),
    ProductionConfiguration: () => import('../modules/configuration/views/Production.vue'),

    ...TagsModule,
    ContextMenu,
    DevContainer,
}
