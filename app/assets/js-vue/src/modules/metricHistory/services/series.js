const pad = value => String(value).padStart(2, '0')

export function monthKey(date) {
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}`
}

export function shiftMonth(key, delta) {
    const [year, month] = key.split('-').map(Number)
    return monthKey(new Date(year, month - 1 + delta, 1))
}

export function daysInMonth(key) {
    const [year, month] = key.split('-').map(Number)
    return new Date(year, month, 0).getDate()
}

export function monthRange(key) {
    return { from: `${key}-01`, to: `${key}-${pad(daysInMonth(key))}` }
}

export function findSeries(series, metric, { dimension = null, periodKey = null } = {}) {
    return series.filter(s => s.metric === metric
        && (dimension === null || s.dimension === dimension)
        && (periodKey === null || s.periodKey === periodKey))
}

/**
 * Wartości serii ułożone po dniach miesiąca (indeks 0 = 1. dzień). Dzień bez zapisu to null,
 * a nie zero - wykres pokazuje lukę. Kilka serii (np. działy jednej listy) jest sumowanych.
 */
export function valuesByDay(seriesList, key, field = 'factorSum') {
    const values = new Array(daysInMonth(key)).fill(null)
    for (const series of seriesList) {
        for (const point of series.points) {
            if (!point.date.startsWith(key)) {
                continue
            }
            const index = Number(point.date.slice(8, 10)) - 1
            values[index] = (values[index] ?? 0) + (point[field] ?? 0)
        }
    }
    return values
}

/**
 * Przyrost dzienny z serii narastającej od 1. dnia miesiąca. Pierwszego dnia licznik startuje
 * od zera, więc przyrost to sama wartość; brak któregoś z dni daje lukę.
 */
export function toDaily(cumulative) {
    return cumulative.map((value, index) => {
        if (value === null) {
            return null
        }
        if (index === 0) {
            return value
        }
        const previous = cumulative[index - 1]
        return previous === null ? null : value - previous
    })
}

export function ratio(numerators, denominators) {
    return numerators.map((value, index) => {
        const total = (value ?? 0) + (denominators[index] ?? 0)
        if (value === null && denominators[index] === null) {
            return null
        }
        return total > 0 ? Math.round(1000 * (value ?? 0) / total) / 10 : null
    })
}

export function backfilledDays(seriesList, key) {
    const days = new Set()
    for (const series of seriesList) {
        for (const point of series.points) {
            if (point.source === 'backfill' && point.date.startsWith(key)) {
                days.add(Number(point.date.slice(8, 10)) - 1)
            }
        }
    }
    return days
}

/**
 * "2026-W45" -> poniedziałek tego tygodnia ISO.
 */
export function isoWeekMonday(periodKey) {
    const [year, week] = periodKey.split('-W').map(Number)
    const jan4 = new Date(year, 0, 4)
    const monday = new Date(jan4)
    monday.setDate(jan4.getDate() - ((jan4.getDay() + 6) % 7) + (week - 1) * 7)
    return monday
}
