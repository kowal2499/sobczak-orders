/**
 * Search term and page of a paginated list, kept in the URL so that back/forward and a reload restore them.
 */
export function listQuery(route) {
    return {
        q: route.query.q || '',
        page: Math.max(1, Number(route.query.page) || 1),
    }
}

// Defaults are dropped, so the first page of an unfiltered list keeps a clean URL.
export function queryWith(route, changes) {
    const next = { ...listQuery(route), ...changes }
    const query = {}
    if (next.q) {
        query.q = next.q
    }
    if (next.page > 1) {
        query.page = String(next.page)
    }
    return query
}
