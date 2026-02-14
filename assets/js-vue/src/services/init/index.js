import store from "../../store";
import * as TYPES from "../../store/types"

export default async (user) => {
    await store.dispatch(`ui/${TYPES.ACTION_ENABLE_BUSY_STATE}`, 'Inicjalizacja')
    await Promise.all([
        store.dispatch(`user/${TYPES.ACTION_USER_FETCH_GRANTS}`, { force: false, userId: user.id }),
    ])
    await store.dispatch(`ui/${TYPES.ACTION_DISABLE_BUSY_STATE}`)
}