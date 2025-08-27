import * as TYPES from '../types'
import Vue from 'vue'

export default {
    [TYPES.MUTATION_SET_USER_GRANTS](state, grants) {
        state.grants = grants;

        const user = Vue.prototype.$user
        user.setGrants(grants)

        console.log(user.grants)
    }
};