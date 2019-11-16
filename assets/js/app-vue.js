import Vue from 'vue';
import _ from 'lodash';
import '../css/app-vue.scss';
import components from './vue/components/root-components';
import access from "./vue/services/privilages";
import helpers from "./vue/helpers";

import routing from "./vue/api/routing";

import VueI18n from 'vue-i18n'
import translationsGeneral from './translations/general';
import translationsDashboard from './translations/dashboard';
import translationsOrders from './translations/orders';
import translationsProduction from './translations/production';

window.Event = new Vue();

document.addEventListener('DOMContentLoaded', () => {

    // inicjalizacja użytkownika
    const userData = document.querySelector('[data-user-info]');
    let user;

    if (userData) {
        user = JSON.parse(decodeURIComponent(userData.dataset.userInfo));
    }

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

    Vue.use(VueI18n);

    const i18n = new VueI18n({locale: user.locale, fallbackLocale: 'pl',
        messages: {
            pl: {
                ...translationsDashboard.pl,
                ...translationsGeneral.pl,
                ...translationsOrders.pl,
                ...translationsProduction.pl,
            },
            en: {
                ...translationsDashboard.en,
                ...translationsGeneral.en,
                ...translationsOrders.en,
                ...translationsProduction.en,
            }
        }
    });

    new Vue({
        el: '#app',
        components,
        i18n
    });


});

window.addEventListener("load", function(event) {
    // var blur = document.querySelector('.blur');
    // blur.style.display = 'none';
});