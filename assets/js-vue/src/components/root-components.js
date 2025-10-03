import NewOrder from './orders/NewOrder';
import OrdersList from './orders/list/OrdersList';
import ProductionList from './production/ProductionList';
import SingleOrder from './orders/single/SingleOrder';
import Dashboard from './dashboard/Dashboard';
import UsersList from './users/UsersList';
import Notifications from './base/Notifications';
import Dropdown from './base/Dropdown';

import MenuItem from './base/MenuItem';
import NavOrders from './navigation/NavOrders';

import TagsModule from "../modules/tags";
import { views as AuthorizationModuleViews } from "../modules/authorization/exports"
import Dashboard2 from '../modules/dashboard/Dashboard';

import ConfigurationModuleWrapper from '../modules/configuration/views/ConfigurationModuleWrapper.vue'
import UserSingle from '../modules/configuration/views/UserSingle.vue'

import App from '../modules/app/views/App.vue'

export default {
    App,
    NewOrder,
    OrdersList,
    ProductionList,
    SingleOrder,
    Dashboard,
    UsersList,
    UserSingle,
    Dropdown,
    MenuItem,
    Notifications,
    NavOrders,
    Dashboard2,
    ConfigurationModuleWrapper,
    ...TagsModule,
    ...AuthorizationModuleViews,
}
