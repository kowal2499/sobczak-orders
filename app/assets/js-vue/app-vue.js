import Vue from 'vue';
import { timeago } from './src/services/timeago';
import moment from "moment";
import {BootstrapVue, BootstrapVueIcons} from 'bootstrap-vue'
import FormLayout from '@/components/base/Form/FormLayout.vue'
import SectionBlock from '@/components/base/SectionBlock.vue'
import SectionBlockTitle from '@/components/base/SectionBlockTitle.vue'
import { privileges, Tasks, User, Roles } from "./src/services/privilages";
import helpers from "./src/helpers";
import i18n from "./i18n";
import store from './src/store';
import * as TYPES from './src/store/types';
import validation from './src/validation/index'
import axios from 'axios';
import { createRouter } from './src/router';
import AppLayout from './src/modules/app/views/AppLayout.vue';

import { library } from '@fortawesome/fontawesome-svg-core'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faSpinner, faUser, faHammer, faLink, faTimesCircle,
    faCheckCircle, faShoppingCart, faHashtag, faInfo, faInfoCircle, faTimes, faCheck,
    faClock, faCalendarDay, faCogs, faSquare, faDownload, faSave, faTrash, faBars, faUserPlus,
    faCalendarCheck, faChartLine, faChevronLeft, faChevronRight, faPlus, faSearch, faPhone, faEnvelope,
    faExclamationCircle, faChevronUp, faChevronDown, faArrowRight, faEye, faEyeSlash, faUndo,
    faArrowsAlt, faHome, faCog, faThumbtack, faFilter,
} from '@fortawesome/free-solid-svg-icons'
library.add(faSpinner, faUser, faHammer, faLink, faTimesCircle,
    faCheckCircle, faShoppingCart, faHashtag, faInfo, faInfoCircle, faTimes, faCheck,
    faClock, faCalendarDay, faCogs, faSquare, faDownload, faSave, faTrash, faBars, faUserPlus,
    faCalendarCheck, faChartLine, faChevronLeft, faChevronRight, faPlus, faSearch,
    faPhone, faEnvelope, faExclamationCircle, faChevronUp, faChevronDown, faArrowRight,
    faEye, faEyeSlash, faUndo, faArrowsAlt, faHome, faCog, faThumbtack, faFilter)
Vue.component('font-awesome-icon', FontAwesomeIcon)

import PortalVue from 'portal-vue'
Vue.use(PortalVue)

window.EventBus = new Vue();

async function fetchCurrentUser() {
    const { data } = await axios.get('/me');
    // An expired session makes the firewall redirect the XHR to the login page, which arrives here as HTML.
    if (!data || typeof data !== 'object') {
        window.location.href = '/login';
        return new Promise(() => {});
    }
    return data;
}

document.addEventListener('DOMContentLoaded', async () => {

    const user = await fetchCurrentUser();
    i18n.locale = user.locale;

    Vue.prototype.$user = new User(user);
    store.commit(`user/${TYPES.MUTATION_SET_USER_GRANTS}`, user.grants);
    Vue.prototype.$privilages = Roles;
    Vue.prototype.$helpers = helpers;
    Vue.prototype.$flash = {
        success: content => window.EventBus.$emit('message', { type: 'success', content }),
        danger: content => window.EventBus.$emit('message', { type: 'danger', content }),
        info: content => window.EventBus.$emit('message', { type: 'info', content }),
        warning: content => window.EventBus.$emit('message', { type: 'warning', content }),
    }

    Vue.mixin({
        methods: {
            __mixin_convertNewlinesToHtml: helpers.convertNewlinesToHtml,
            __mixin_customerName: helpers.customerName,
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

    Vue.filter('timeago', value => timeago(value, i18n.locale));

    Vue.filter('roundFloat', value => {
        return (Math.round(value * 100)/100).toFixed(2)
    })

    Vue.component('FormLayout', FormLayout)
    Vue.component('SectionBlock', SectionBlock)
    Vue.component('SectionBlockTitle', SectionBlockTitle)

    // Mobile sidebar toggle (hover-expand is pure CSS; touch/narrow uses this).
    // Delegated on `document` and resolving #wrapper fresh on each click, because
    // Vue mounts on #app and re-renders the burger/backdrop nodes, which would
    // detach any listeners bound to them directly.
    const closeSidebar = () => {
        const wrapper = document.getElementById('wrapper');
        if (wrapper) wrapper.classList.remove('sidebar-open');
    };
    document.addEventListener('click', (e) => {
        const target = e.target;
        if (!target || !target.closest) return;

        if (target.closest('.sidebar-burger')) {
            const wrapper = document.getElementById('wrapper');
            if (wrapper) wrapper.classList.toggle('sidebar-open');
        } else if (target.closest('.sidebar-backdrop') || target.closest('nav.sidebar a[href]')) {
            closeSidebar();
        }
    });

    new Vue({
        el: '#app',
        router: createRouter(Vue.prototype.$user),
        i18n,
        store,
        render: h => h(AppLayout),
    });

});