// Daty w rekordach raportu przychodzą jako ISO ("2026-05-15T00:00:00+02:00") - wszędzie interesuje
// nas wyłącznie część dzienna, więc formatujemy tekstowo, bez tworzenia obiektów Date.

export function fmtDate(value) {
    if (!value) {
        return '-'
    }
    const [y, m, d] = String(value).slice(0, 10).split('-')

    return `${d}.${m}.${y}`
}

export function fmtDayMonth(value) {
    if (!value) {
        return '-'
    }
    const [, m, d] = String(value).slice(0, 10).split('-')

    return `${d}.${m}`
}

export function toDay(value) {
    return new Date(String(value).slice(0, 10))
}
