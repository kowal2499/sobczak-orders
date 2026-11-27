import moment from 'moment'

export function toDay(value) {
  return moment.isMoment(value) ? value.clone().startOf('day') : moment(value, 'YYYY-MM-DD')
}

export function buildColumns(start, end) {
  const columns = []
  const last = toDay(end)
  for (const day = toDay(start); day.isSameOrBefore(last, 'day'); day.add(1, 'day')) {
    columns.push(day.clone())
  }
  return columns
}

export function columnOf(rangeStart, date) {
  return toDay(date).diff(toDay(rangeStart), 'days')
}

export function clipToRange(dateStart, dateEnd, rangeStart, rangeEnd) {
  const lastCol = columnOf(rangeStart, rangeEnd)
  const startCol = columnOf(rangeStart, dateStart)
  const endCol = columnOf(rangeStart, dateEnd)
  if (endCol < 0 || startCol > lastCol) return null
  return { startCol: Math.max(0, startCol), endCol: Math.min(lastCol, endCol) }
}

export function shiftDate(date, days) {
  return toDay(date).add(days, 'days').format('YYYY-MM-DD')
}

export function clampResizeOffset(edge, dateStart, dateEnd, offset) {
  const span = columnOf(dateStart, dateEnd)
  return edge === 'start' ? Math.min(offset, span) : Math.max(offset, -span)
}

export function groupWeeks(columns) {
  return columns.reduce((weeks, day, index) => {
    const weekStart = day.clone().isoWeekday(1).format('YYYY-MM-DD')
    const last = weeks[weeks.length - 1]
    if (last && last.weekStart === weekStart) {
      last.span += 1
    } else {
      weeks.push({ weekStart, startCol: index, span: 1 })
    }
    return weeks
  }, [])
}

export function groupMonths(columns) {
  return columns.reduce((months, day, index) => {
    const last = months[months.length - 1]
    if (last && last.year === day.year() && last.month === day.month()) {
      last.span += 1
    } else {
      months.push({ year: day.year(), month: day.month(), startCol: index, span: 1 })
    }
    return months
  }, [])
}
