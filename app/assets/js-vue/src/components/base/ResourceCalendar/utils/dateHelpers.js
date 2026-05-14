import moment from 'moment'

export function isWeekend(year, month, day) {
  const d = moment([year, month, day]).isoWeekday()
  return d === 6 || d === 7
}

export function isToday(year, month, day) {
  return moment([year, month, day]).isSame(moment(), 'day')
}

export function formatDateRange(dateStart, dateEnd) {
  return `${moment(dateStart).format('DD.MM')} – ${moment(dateEnd).format('DD.MM.YYYY')}`
}
