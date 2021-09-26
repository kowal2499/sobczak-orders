import Vue from 'vue';
import moment from "moment";
import {BootstrapVue, BootstrapVueIcons} from 'bootstrap-vue'
import '../css/app-vue.scss';
import components from './src/components/root-components';
import access from "./src/services/privilages";
import helpers from "./src/helpers";
import routing from "./src/api/routing";
import i18n from "./i18n";

import { library } from '@fortawesome/fontawesome-svg-core'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faSpinner } from '@fortawesome/free-solid-svg-icons'

library.add(faSpinner)
Vue.component('font-awesome-icon', FontAwesomeIcon)

window.EventBus = new Vue();

document.addEventListener('DOMContentLoaded', () => {

    // inicjalizacja użytkownika
    const userData = document.querySelector('[data-user-info]');
    const user = userData ? JSON.parse(decodeURIComponent(userData.dataset.userInfo)) : {};
    i18n.locale = user.locale;

        // inicjalizacja vue
    Vue.prototype.$access = access;
    Vue.prototype.$user = new access.User(user);
    Vue.prototype.$privilages = access.Roles;
    Vue.prototype.$helpers = helpers;

    Vue.mixin({
        methods: {
            __mixin_convertNewlinesToHtml: helpers.convertNewlinesToHtml,
            __mixin_customerName: helpers.customerName,
            __mixin_getRouting: routing.get,
        },
    });

    Vue.use(BootstrapVue);
    Vue.use(BootstrapVueIcons);

    Vue.filter('formatDate', (value, format = null) => {
        let mData = moment(value);
        if (mData.isValid()) {
            return mData.format(format ? format : 'YYYY-MM-DD HH:mm:ss');
        } else {
            return value;
        }
    });

    new Vue({
        el: '#app',
        components,
        i18n
    });
});