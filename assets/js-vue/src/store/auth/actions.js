import * as TYPES from "../types";
import { fetchGrants, fetchModules } from "@/modules/authorization/repository/authorizatonRepository"

export default {
    [TYPES.ACTION_AUTH_FETCH_GRANTS]({ state, commit}, force = false) {
        if (state.grants === undefined || force) {
            return fetchGrants().then(({data}) => {
                commit(TYPES.MUTATION_AUTH_SET_GRANTS, data);
                return data
            })
        }
        return Promise.resolve(state.grants)
    },

    [TYPES.ACTION_AUTH_FETCH_MODULES]({ state, commit }, force = false) {
        if (state.modules === undefined || force) {
            return fetchModules().then(({data}) => {
                commit(TYPES.MUTATION_AUTH_SET_MODULES, data);
                return data
            })
        }
        return Promise.resolve(state.modules)
    }
}