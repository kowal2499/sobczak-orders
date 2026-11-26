import { dateToString, parseYMD } from '@/services/datesService'
import { findSeries, valuesByDay } from '../../metricHistory/services/series'

export const STATE_WINDOW_DAYS = 30

export function monthKeyOf(ymd) {
    return ymd.slice(0, 7)
}

function lastDays(endYmd, count) {
    const end = parseYMD(endYmd)
    return Array.from({ length: count }, (_, index) => {
        const day = new Date(end)
        day.setDate(end.getDate() - (count - 1 - index))
        return dateToString(day)
    })
}

function valuesForDates(seriesList, dates, field) {
    const byDate = new Map(dates.map(date => [date, null]))
    for (const series of seriesList) {
        for (const point of series.points) {
            if (byDate.has(point.date)) {
                byDate.set(point.date, (byDate.get(point.date) ?? 0) + (point[field] ?? 0))
            }
        }
    }
    return dates.map(date => byDate.get(date))
}

// jeden punkt to jeszcze nie dynamika
function hasValues(values) {
    return values.filter(value => value !== null).length >= 2
}

function lastValue(values) {
    for (let index = values.length - 1; index >= 0; index--) {
        if (values[index] !== null) {
            return { value: values[index], index }
        }
    }
    return null
}

function firstValue(values) {
    const index = values.findIndex(value => value !== null)
    return index === -1 ? null : values[index]
}

/**
 * Stan na dzień: ostatnie dni zakończone dniem `endYmd` (dzisiaj albo końcem wybranego miesiąca).
 */
function stateTrend(seriesList, endYmd, field, unit) {
    const dates = lastDays(endYmd, STATE_WINDOW_DAYS)
    const values = valuesForDates(seriesList, dates, field)
    if (!hasValues(values)) {
        return null
    }

    return {
        kind: 'state',
        unit,
        dates,
        values,
        current: lastValue(values).value,
        start: firstValue(values),
    }
}

/**
 * Przepływ narastający od 1. dnia miesiąca.
 */
function flowTrend(valuesFor, monthKey, unit) {
    const values = valuesFor(monthKey)
    if (!hasValues(values)) {
        return null
    }

    return {
        kind: 'flow',
        unit,
        dates: values.map((_, index) => `${monthKey}-${String(index + 1).padStart(2, '0')}`),
        values,
    }
}

export function ordersPendingTrend(series, endYmd) {
    return stateTrend(findSeries(series, 'orders_pending'), endYmd, 'factorSum', 'factors')
}

export function ordersFinishedTrend(series, monthKey) {
    return flowTrend(key => valuesByDay(findSeries(series, 'orders_finished'), key), monthKey, 'factors')
}

export function departmentsBonusTrend(series, monthKey) {
    return flowTrend(key => valuesByDay(findSeries(series, 'departments_bonus'), key), monthKey, 'factors')
}

// historia jest liczona z widełkami obowiązującymi dla rozliczeń, nie z podglądu z kafla
export function onTimeTrend(series, monthKey) {
    const trend = flowTrend(key => valuesByDay(findSeries(series, 'on_time_in'), key), monthKey, 'factors')

    return trend && { ...trend, note: 'defaultTolerance' }
}

// listy produkcyjne mają osobny zapis per zamówienie ("lista@działy"), bo tyle pokazuje kafel
export function attentionTrend(series, list, endYmd) {
    const seriesList = series.filter(s => s.metric === 'attention_lists'
        && (s.dimension === list || s.dimension.startsWith(`${list}@`)))

    return stateTrend(seriesList, endYmd, 'itemCount', 'orders')
}
