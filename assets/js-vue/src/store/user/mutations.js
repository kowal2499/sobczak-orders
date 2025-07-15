import * as TYPES from '../types'
export default {
    [TYPES.MUTATION_SET_USER_GRANTS](state, grants) {
        state.grants = grants;
    }
};