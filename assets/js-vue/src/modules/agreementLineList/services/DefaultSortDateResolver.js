const grantMap = {
    'production.show.production_date': 'dateConfirmed_asc',
    'production.show.gluing': 'dpt01DateEnd_asc',
    'production.show.cnc': 'dpt02DateEnd_asc',
    'production.show.grinding': 'dpt03DateEnd_asc',
    'production.show.laquering': 'dpt04DateEnd_asc',
    'production.show.packing': 'dpt05DateEnd_asc',
}

/**
 * @param {User} user
 * @returns {string}
 */
function resolveDefaultOrder(user) {
    const orderBy = Object.keys(grantMap).find(grant => user.can(grant))
    return orderBy ? grantMap[orderBy] : 'dateReceive_asc'
}

export { resolveDefaultOrder }