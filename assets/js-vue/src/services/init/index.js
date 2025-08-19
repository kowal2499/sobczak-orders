import store from "../../store";
import * as TYPES from "../../store/types"

export default async () => {
    await store.dispatch(`ui/${TYPES.ACTION_ENABLE_BUSY_STATE}`, 'Inicjalizacja')
    await Promise.all([
        store.dispatch(`user/${TYPES.ACTION_FETCH_GRANTS}`)
    ])
    await store.dispatch(`ui/${TYPES.ACTION_DISABLE_BUSY_STATE}`)
}