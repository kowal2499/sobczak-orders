import moment from 'moment'
import { capacityPercent, capacityVariant, weekNumberLabel } from '@/modules/schedule/services/capacityScale'

export const PLAN_EVENT_PREFIX = 'plan-'

const RANGE_TAIL_DAYS = 14
const FORMAT = 'YYYY-MM-DD'

export function isPlanEvent(event) {
    return typeof event.id === 'string' && event.id.startsWith(PLAN_EVENT_PREFIX)
}

export function planToEvents(form, departmentName = slug => slug) {
    return form
        .filter(row => row.dateStart && row.dateEnd)
        .map(row => ({
            id: PLAN_EVENT_PREFIX + row.slug,
            resourceId: row.slug,
            dateStart: row.dateStart,
            dateEnd: row.dateEnd,
            eventType: 'plan',
            orderName: departmentName(row.slug),
            orderStatus: 'planned',
        }))
}

export function applyEventToForm(form, { resourceId, dateStart, dateEnd }) {
    return form.map(row => (row.slug === resourceId ? { ...row, dateStart, dateEnd } : { ...row }))
}

const LOAD_ALPHAS = [0.12, 0.24, 0.36, 0.48]

function loadAlpha(ratio) {
    if (ratio <= 0.25) return LOAD_ALPHAS[0]
    if (ratio <= 0.5) return LOAD_ALPHAS[1]
    if (ratio <= 0.75) return LOAD_ALPHAS[2]
    return LOAD_ALPHAS[3]
}

export function loadToBackgroundEvents(load) {
    return Object.entries(load || {}).flatMap(([slug, days]) => {
        const max = Math.max(0, ...Object.values(days))
        return Object.entries(days)
            .filter(([, count]) => count > 0)
            .map(([date, count]) => ({
                id: `load-${slug}-${date}`,
                resourceId: slug,
                dateStart: date,
                dateEnd: date,
                eventType: 'background',
                color: `rgba(var(--colorPrimaryRgb), ${loadAlpha(count / max)})`,
            }))
    })
}

// A mistyped year must not produce tens of thousands of calendar columns
const SANE_WINDOW_YEARS = 1

export function defaultRange(form, confirmedDate, today) {
    const earliest = moment(today, FORMAT).subtract(SANE_WINDOW_YEARS, 'years')
    const latest = moment(confirmedDate, FORMAT).add(SANE_WINDOW_YEARS, 'years')
    const dates = form
        .flatMap(row => [row.dateStart, row.dateEnd])
        .filter(Boolean)
        .map(d => moment(d, FORMAT))
        .filter(d => d.isValid() && d.isBetween(earliest, latest, 'day', '[]'))
    const start = moment.min([moment(today, FORMAT), ...dates]).clone().isoWeekday(1)
    // today stays in view, otherwise a plan lying in the past hides the current load
    const end = moment.max([moment(confirmedDate, FORMAT), moment(today, FORMAT), ...dates]).clone().add(RANGE_TAIL_DAYS, 'days')
    return { start: start.format(FORMAT), end: end.format(FORMAT) }
}

export function changedSlugs(previous, next) {
    return next
        .filter(row => {
            const before = previous.find(r => r.slug === row.slug)
            return !before || before.dateStart !== row.dateStart || before.dateEnd !== row.dateEnd
        })
        .map(row => row.slug)
}

export function capacityByWeek(days) {
    return (days || []).reduce((weeks, day) => {
        if (day.weekStart && !weeks[day.weekStart]) {
            weeks[day.weekStart] = { capacity: day.capacity, burned: day.capacityBurned }
        }
        return weeks
    }, {})
}

export function weekHeaderCell(weekStart, week) {
    const label = weekNumberLabel(weekStart)
    if (!week || !week.capacity) return { label, percent: null, variant: null }
    const percent = capacityPercent(week.capacity, week.burned)
    return { label: `${label} · ${percent}%`, percent, variant: capacityVariant(percent) }
}

export function onlyDepartments(items, selected, slugOf) {
    if (!selected || !selected.length) return items
    const set = new Set(selected)
    return items.filter(item => set.has(slugOf(item)))
}
