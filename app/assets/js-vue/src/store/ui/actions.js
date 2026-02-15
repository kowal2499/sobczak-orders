import * as TYPES from "../types";

export default {
    [TYPES.ACTION_ENABLE_BUSY_STATE]({ commit }, message) {
        commit(TYPES.MUTATION_SET_BUSY_STATE, { busy: true, message });
    },

    [TYPES.ACTION_DISABLE_BUSY_STATE]({ commit }) {
        commit(TYPES.MUTATION_SET_BUSY_STATE, { busy: false, message: null });
    }
}