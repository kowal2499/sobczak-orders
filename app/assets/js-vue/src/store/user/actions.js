import * as TYPES from "../types";
import UsersApi from "@/modules/configuration/repository/users";

export default {
    async [TYPES.ACTION_USER_FETCH_USERS]({ state, commit }, force = false) {
        if (force || state.users === undefined) {
            return UsersApi.fetchUsers(true)
                .then(({data}) => {
                    commit(TYPES.MUTATION_USER_SET_USERS, data)
                })
        }
        return Promise.resolve(state.users)
    }
}
