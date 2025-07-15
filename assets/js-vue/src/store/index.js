import Vue from 'vue'
import Vuex from 'vuex'
Vue.use(Vuex);


import ui from './ui';
import user from './user';

import actions from './actions'
import mutations from './mutations'
import getters from './getters'
import { getState } from "./state";

export default new Vuex.Store({
    modules: {
        ui,
        user
    },
    actions,
    mutations,
    getters,
    state: getState()
});

