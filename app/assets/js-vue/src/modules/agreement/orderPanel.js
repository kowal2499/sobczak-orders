import qs from 'qs'

export const PANEL_PARAM = 'panel'
export const DEPARTMENT_PARAM = 'panelDepartment'

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
    delete rest[PANEL_PARAM]
    delete rest[DEPARTMENT_PARAM]
    return rest
}

export function panelQuery(query) {
    const panel = {}
    if (query[PANEL_PARAM]) {
        panel[PANEL_PARAM] = query[PANEL_PARAM]
    }
    if (query[DEPARTMENT_PARAM]) {
        panel[DEPARTMENT_PARAM] = query[DEPARTMENT_PARAM]
    }
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
