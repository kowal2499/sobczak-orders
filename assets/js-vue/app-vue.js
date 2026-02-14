import Vue from 'vue';
import moment from "moment";
import {BootstrapVue, BootstrapVueIcons} from 'bootstrap-vue'
import components from './src/components/root-components';
import FormLayout from '@/components/base/Form/FormLayout.vue'
import { privileges, Tasks, User, Roles } from "./src/services/privilages";
import helpers from "./src/helpers";
import routing from "./src/api/routing";
import i18n from "./i18n";
import store from './src/store';
import init from "./src/services/init";
import validation from './src/validation/index'

import { library } from '@fortawesome/fontawesome-svg-core'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faSpinner, faUser, faHammer, faLink, faTimesCircle,
    faCheckCircle, faShoppingCart, faHashtag, faInfo, faInfoCircle, faTimes, faCheck,
    faClock, faCalendarDay, faCogs, faSquare, faDownload, faSave, faTrash
} from '@fortawesome/free-solid-svg-icons'
library.add(faSpinner, faUser, faHammer, faLink, faTimesCircle,
    faCheckCircle, faShoppingCart, faHashtag, faInfo, faInfoCircle, faTimes, faCheck,
    faClock, faCalendarDay, faCogs, faSquare, faDownload, faSave, faTrash)
Vue.component('font-awesome-icon', FontAwesomeIcon)

window.EventBus = new Vue();

document.addEventListener('DOMContentLoaded', () => {

    // inicjalizacja użytkownika
    const userData = document.querySelector('[data-user-info]');
    const user = userData ? JSON.parse(decodeURIComponent(userData.dataset.userInfo)) : {};
    i18n.locale = user.locale;

    // inicjalizacja vue
    Vue.prototype.$user = new User(user);
    Vue.prototype.$privilages = Roles;
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

    Vue.filter('roundFloat', value => {
        return (Math.round(value * 100)/100).toFixed(2)
    })

    Vue.component('FormLayout', FormLayout)

    init(user).then(() => {
        new Vue({
            el: '#app',
            components,
            i18n,
            store,
        });
    })

});