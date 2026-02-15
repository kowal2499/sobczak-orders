import * as TYPES from '../types'
export default {
    [TYPES.MUTATION_SET_BUSY_STATE](state, { busy, message }) {
        state.busyState = busy;
        state.busyStateMessage = message;
    }
}