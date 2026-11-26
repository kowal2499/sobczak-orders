/**
 * The capacity endpoint sends each week's order lines once ({ days, agreementLinesByWeek });
 * consumers still read them per day, so every day of a week gets the same array reference.
 */
export function expandCapacityDays(payload) {
    const linesByWeek = payload?.agreementLinesByWeek || {}

    return (payload?.days || []).map(day => ({
        ...day,
        agreementLines: linesByWeek[day.weekStart] || [],
    }))
}

export function withExpandedCapacityDays(request) {
    return request.then(response => ({ ...response, data: expandCapacityDays(response.data) }))
}
