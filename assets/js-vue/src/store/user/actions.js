import * as TYPES from "../types";
import axios from "axios";
import { get, set } from "../../services/localstorageCache"

export default {
    async [TYPES.ACTION_FETCH_GRANTS]({ commit }, force = false) {
        let grants
        if (!force) {
            grants = get('-user-grants')
        }
        if (grants === undefined) {
            const response = await axios.get('/authorization/grants')
            grants = response.data.data
            set('-user-grants', grants, 5)
        }
        commit(TYPES.MUTATION_SET_USER_GRANTS, grants);
    }
}