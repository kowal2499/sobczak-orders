import Vue from 'vue';
import '../css/app-vue.scss';
import components from './vue/components/root-components';
import access from "./vue/services/privilages";

window.Event = new Vue();

Vue.prototype.$access = access;

new Vue({
    el: '#app',
    components,
});

window.addEventListener("load", function(event) {
    // var blur = document.querySelector('.blur');
    // blur.style.display = 'none';
});