import qs from 'qs'

export const PANEL_PARAM = 'panel'
export const DEPARTMENT_PARAM = 'panelDepartment'
export const EDIT_PARAM = 'editOrder'

const DRAWER_PARAMS = [PANEL_PARAM, DEPARTMENT_PARAM, EDIT_PARAM]

/*
 * The order panel drawer lives in the query of whatever page opened it (?panel=4147), so back/forward,
 * a reload and a copied link all restore it. The orders and production lists still write their filters
 * with history.pushState, bypassing the router, so the current query is read from the address bar.
 */
function currentQuery() {
    return qs.parse(window.location.search, { ignoreQueryPrefix: true })
}

export function withoutPanel(query) {
    const rest = { ...query }
    DRAWER_PARAMS.forEach(param => delete rest[param])
    return rest
}

export function panelQuery(query) {
    const panel = {}
    DRAWER_PARAMS.filter(param => query[param]).forEach(param => panel[param] = query[param])
    return panel
}

// For the lists that rebuild their whole query string on every filter change.
export function currentPanelQuery() {
    return panelQuery(currentQuery())
}

export function panelOf(route) {
    const lineId = Number(route.query[PANEL_PARAM])
    if (!(lineId > 0)) {
        return null
    }
    return { lineId, department: route.query[DEPARTMENT_PARAM] || null }
}

export function openOrderPanel(router, lineId, department = null) {
    const query = { ...withoutPanel(currentQuery()), [PANEL_PARAM]: String(lineId) }
    if (department) {
        query[DEPARTMENT_PARAM] = department
    }
    return router.push({ query }).catch(() => {})
}

export function closeOrderPanel(router) {
    return router.replace({ query: withoutPanel(currentQuery()) }).catch(() => {})
}

export function orderEditOf(route) {
    const agreementId = Number(route.query[EDIT_PARAM])
    return agreementId > 0 ? agreementId : null
}

export function openOrderEdit(router, agreementId) {
    const query = { ...withoutPanel(currentQuery()), [EDIT_PARAM]: String(agreementId) }
    return router.push({ query }).catch(() => {})
}
