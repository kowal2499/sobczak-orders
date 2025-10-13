import * as TYPES from '../types'
export default {
    [TYPES.MUTATION_AUTH_SET_MODULES](state, modules) {
        state.modules = modules
    },
    [TYPES.MUTATION_AUTH_SET_GRANTS](state, grants) {
        state.grants = grants
    },
    [TYPES.MUTATION_AUTH_SET_ROLES](state, roles) {
        state.roles = roles
    }
}