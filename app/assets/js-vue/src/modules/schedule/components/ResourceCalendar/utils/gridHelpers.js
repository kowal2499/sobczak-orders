export const DAY_WIDTH = 40
export const RESOURCE_WIDTH = 180
export const EVENT_HEIGHT = 28

export function assignLanes(events) {
  const lanes = []
  return events.map(e => {
    const startDay = e.clippedStart.date()
    const endDay = e.clippedEnd.date()
    let lane = lanes.findIndex(laneEnd => laneEnd < startDay)
    if (lane === -1) { lane = lanes.length; lanes.push(0) }
    lanes[lane] = endDay
    return { ...e, lane }
  })
}

export function eventPositionStyle(event) {
  return {
    left: RESOURCE_WIDTH + (event.clippedStart.date() - 1) * DAY_WIDTH + 'px',
    width: Math.max(1, event.clippedEnd.date() - event.clippedStart.date() + 1) * DAY_WIDTH - 2 + 'px',
    top: event.lane * EVENT_HEIGHT + 2 + 'px'
  }
}

export const STATUS_COLORS = {
  pending:     { bg: '#fff3cd', border: '#ffc107', text: '#856404' },
  in_progress: { bg: '#cff4fc', border: '#0dcaf0', text: '#055160' },
  completed:   { bg: '#d1e7dd', border: '#198754', text: '#0a3622' },
  cancelled:   { bg: '#f8d7da', border: '#dc3545', text: '#58151c' }
}

export function eventColors(event) {
  const status = STATUS_COLORS[event.orderStatus] || STATUS_COLORS.pending
  return {
    background: event.color || status.bg,
    borderColor: status.border,
    color: status.text
  }
}
