import moment from 'moment'

// One scale for every view of weekly capacity, so the dashboard metric and the planning calendar agree
export function capacityPercent(capacity, burned) {
    if (!capacity || capacity <= 0) return 0
    return Math.round((burned / capacity) * 100)
}

export function capacityVariant(percent) {
    if (percent <= 25) return 'success'
    if (percent <= 50) return 'info'
    if (percent <= 75) return 'warning'
    return 'danger'
}

export function weekNumberLabel(date) {
    return `T${moment(date, 'YYYY-MM-DD').isoWeek()}`
}
