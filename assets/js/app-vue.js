import Vue from 'vue';
import '../css/app-vue.scss';
import components from './vue/components/root-components';
import access from "./vue/services/privilages";
import helpers from "./vue/helpers";
import routing from "./vue/api/routing";


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

    new Vue({
        el: '#app',
        components,
    });


});

window.addEventListener("load", function(event) {
    // var blur = document.querySelector('.blur');
    // blur.style.display = 'none';
});