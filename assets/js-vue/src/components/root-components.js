import NewOrder from './orders/NewOrder';
import OrdersList from './orders/list/OrdersList';
import ProductionList from './production/ProductionList';
import SingleOrder from './orders/single/SingleOrder';
import Dashboard from './dashboard/Dashboard';
import UsersList from './users/UsersList';
import UserSingle from './users/UserSingle';
import Notifications from './base/Notifications';
import Dropdown from './base/Dropdown';

import MenuItem from './base/MenuItem';
import NavOrders from './navigation/NavOrders';

import TagsModule from "../modules/tags";
import Dashboard2 from '../modules/dashboard/Dashboard';

export default {
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
    ...TagsModule,
    Dashboard2,
}