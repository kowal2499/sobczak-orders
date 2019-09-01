import Vue from 'vue';
import components from './vue/components/root-components';

window.Event = new Vue();

new Vue({
    el: '#app',
    components,
});

window.addEventListener("load", function(event) {
    // var blur = document.querySelector('.blur');
    // blur.style.display = 'none';
});