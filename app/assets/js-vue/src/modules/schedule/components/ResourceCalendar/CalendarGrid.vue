<template>
  <div class="calendar-wrapper" ref="wrapper">
   <div class="calendar-grid-inner">
    <!-- Current time indicator -->
    <div
      v-if="nowLineLeft !== null"
      class="now-line"
      :style="{ left: nowLineLeft + 'px' }"
    />

    <div
      v-for="marker in positionedMarkers"
      :key="'marker-' + marker.date"
      class="date-marker"
      :style="{ left: marker.left + 'px' }"
    />

    <div class="calendar-sticky-head">
    <div v-if="showMonths" class="calendar-month-row">
      <div class="resource-label month-corner" />
      <div
        v-for="month in months"
        :key="month.year + '-' + month.month"
        class="month-header-cell"
        :style="{ width: month.span * dayWidth + 'px' }"
      >
        {{ month.span >= 3 ? monthLabel(month) : '' }}
      </div>
    </div>

    <div v-if="weekHeader" class="calendar-week-row">
      <div class="resource-label week-corner" />
      <div
        v-for="week in weeks"
        :key="week.weekStart"
        class="week-header-cell"
        :class="weekHeaderOf(week).variant ? 'week-header-cell--' + weekHeaderOf(week).variant : null"
        :style="{ width: week.span * dayWidth + 'px' }"
        :title="weekHeaderOf(week).title || null"
      >
        <div class="week-header-label">{{ week.span >= 3 ? weekHeaderOf(week).label : '' }}</div>
        <div v-if="weekHeaderOf(week).percent !== null && weekHeaderOf(week).percent !== undefined" class="week-header-bar">
          <div
            :class="'bg-' + weekHeaderOf(week).variant"
            :style="{ width: Math.min(weekHeaderOf(week).percent, 100) + '%' }"
          />
        </div>
      </div>
    </div>

    <!-- Header row: corner + day numbers -->
    <div class="calendar-header-row">
      <div class="resource-label header-corner">Zasób</div>
      <div
        v-for="col in dayColumns"
        :key="col.index"
        :title="col.isHoliday ? col.holidayName : null"
        class="day-header-cell"
        :class="{
          'is-today': col.isToday,
          'is-holiday': col.isHoliday,
          'is-month-start': col.isMonthStart
        }"
      >
        <div class="day-number">{{ col.day }}</div>
        <div class="day-name">{{ col.dayName }}</div>
      </div>
    </div>
    </div>

    <!-- Resource rows -->
    <div
      v-for="resource in resources"
      :key="resource.id"
      class="resource-row"
      :style="{ height: rowHeight(resource.id) + 'px' }"
    >
      <!-- Sticky resource label -->
      <div class="resource-label resource-row-label">
        <slot :name="'resource-label-' + resource.id" :resource="resource">
          <span class="resource-name">
            {{ resource.name }}
            <small v-if="resource.note" class="resource-note">{{ resource.note }}</small>
          </span>
        </slot>
      </div>

      <!-- Day cells (background grid) -->
      <div class="day-cells">
        <div
          v-for="col in dayColumns"
          :key="col.index"
          class="day-cell"
          :title="cellTitle ? cellTitle(resource.id, col.date) || null : null"
          :class="{
            'is-selected': isCellSelected(resource.id, col.index),
            'is-today': col.isToday,
            'is-holiday': col.isHoliday,
            'is-month-start': col.isMonthStart,
            'is-readonly': !interactive
          }"
          :data-resource-id="resource.id"
          :data-col="col.index"
          @mousedown.prevent="$emit('cell-mousedown', resource.id, col.index, $event)"
          @mouseenter="$emit('cell-mouseenter', resource.id, col.index)"
          @mouseup="$emit('cell-mouseup', resource.id, col.index)"
        />
      </div>

      <!-- Background events (behind normal events) -->
      <div
        v-for="bgEvent in backgroundEventsFor(resource.id)"
        :key="'bg-' + bgEvent.id"
        class="background-event"
        :style="bgEventStyle(bgEvent)"
      />

      <!-- Normal event blocks -->
      <CalendarEvent
        v-for="event in normalEventsFor(resource.id)"
        :key="event.id"
        :event="event"
        :is-dragging="isDraggingEvent(event.id)"
        :interactive="isEventInteractive(event)"
        @mousedown.native.prevent="$emit('event-mousedown', event, $event)"
        @resize-mousedown="$emit('resize-mousedown', event, $event)"
        @resize-start-mousedown="$emit('resize-start-mousedown', event, $event)"
        @click.native.stop="$emit('event-click', event)"
      >
        <template #default="slotProps">
          <!-- Priority: resource-{id} > event-type-{type} > event-type-default > built-in -->
          <template v-if="$slots['resource-' + event.resourceId] || $scopedSlots['resource-' + event.resourceId]">
            <slot :name="'resource-' + event.resourceId" v-bind="slotProps" />
          </template>
          <template v-else-if="$slots['event-type-' + event.eventType] || $scopedSlots['event-type-' + event.eventType]">
            <slot :name="'event-type-' + event.eventType" v-bind="slotProps" />
          </template>
          <template v-else>
            <slot name="event-type-default" v-bind="slotProps">
              <span class="event-label">{{ slotProps.event.orderName }}</span>
            </slot>
          </template>
        </template>
      </CalendarEvent>
    </div>
   </div>
  </div>
</template>

<script>
import moment from 'moment'
import CalendarEvent from './CalendarEvent.vue'
import { columnOf, groupMonths, groupWeeks } from './utils/timeline'

const MONTH_KEYS = [
  '_january', '_february', '_march', '_april', '_may', '_june',
  '_july', '_august', '_september', '_october', '_november', '_december'
]
import { DAY_WIDTH, RESOURCE_WIDTH, EVENT_HEIGHT } from './utils/gridHelpers'

export default {
  name: 'CalendarGrid',
  components: { CalendarEvent },
  props: {
    resources: { type: Array, required: true },
    columns: { type: Array, required: true },
    positionedEvents: { type: Object, required: true },
    selectionState: { type: Object, default: null },
    dragState: { type: Object, default: null },
    interactive: { type: Boolean, default: true },
    holidays: { type: Array, default: () => [] },
    canEditEvent: { type: Function, default: null },
    cellTitle: { type: Function, default: null },
    weekHeader: { type: Function, default: null },
    showMonths: { type: Boolean, default: false },
    markers: { type: Array, default: () => [] }
  },
  data() {
    return {
      now: moment(),
      nowTimer: null
    }
  },
  computed: {
    dayWidth() {
      return DAY_WIDTH
    },
    weeks() {
      return groupWeeks(this.columns)
    },
    months() {
      return groupMonths(this.columns)
    },
    // A marker sits on the right edge of its day: the day itself is still allowed
    positionedMarkers() {
      if (!this.columns.length) return []
      return this.markers
        .map(marker => ({ ...marker, col: columnOf(this.columns[0], marker.date) }))
        .filter(marker => marker.col >= 0 && marker.col < this.columns.length)
        .map(marker => ({ ...marker, left: RESOURCE_WIDTH + (marker.col + 1) * DAY_WIDTH - 2 }))
    },
    nowLineLeft() {
      if (!this.columns.length) return null
      const dayOffset = columnOf(this.columns[0], this.now)
      if (dayOffset < 0 || dayOffset >= this.columns.length) return null
      const fractionOfDay = (this.now.hours() * 60 + this.now.minutes()) / (24 * 60)
      return RESOURCE_WIDTH + (dayOffset + fractionOfDay) * DAY_WIDTH
    },
    holidayMap() {
      const map = new Map()
      this.holidays.forEach(h => map.set(h.date, h.description || ''))
      return map
    },
    dayColumns() {
      const today = moment()
      return this.columns.map((m, index) => {
        const date = m.format('YYYY-MM-DD')
        return {
          index,
          date,
          day: m.date(),
          isToday: m.isSame(today, 'day'),
          isMonthStart: index > 0 && m.date() === 1,
          isHoliday: this.holidayMap.has(date),
          holidayName: this.holidayMap.get(date) || '',
          dayName: this.$t(`schedule.dayAbbr.${m.day()}`)
        }
      })
    }
  },
  mounted() {
    this.nowTimer = setInterval(() => {
      this.now = moment()
    }, 60 * 1000)
  },
  beforeDestroy() {
    if (this.nowTimer) clearInterval(this.nowTimer)
  },
  methods: {
    rowHeight(resourceId) {
      // A resource may request an explicit fixed height (e.g. compact single-bar rows).
      const resource = this.resources.find(r => r.id === resourceId)
      if (resource && resource.rowHeight) return resource.rowHeight
      const data = this.positionedEvents[resourceId]
      if (!data) return 60
      const maxLane = data.events.reduce((max, e) => Math.max(max, e.lane || 0), 0)
      const laneCount = data.events.length > 0 ? maxLane + 1 : 0
      return Math.max(60, laneCount * EVENT_HEIGHT + 10)
    },

    monthLabel(month) {
      return `${this.$t(MONTH_KEYS[month.month])} ${month.year}`
    },

    weekHeaderOf(week) {
      return this.weekHeader(week.weekStart) || {}
    },

    normalEventsFor(resourceId) {
      const data = this.positionedEvents[resourceId]
      if (!data) return []
      return data.events.filter(e => e.eventType !== 'background')
    },

    backgroundEventsFor(resourceId) {
      const data = this.positionedEvents[resourceId]
      if (!data) return []
      return data.events.filter(e => e.eventType === 'background')
    },

    bgEventStyle(event) {
      const left = RESOURCE_WIDTH + event.startCol * DAY_WIDTH
      const width = (event.endCol - event.startCol + 1) * DAY_WIDTH
      return {
        position: 'absolute',
        left: left + 'px',
        width: width + 'px',
        top: 0,
        bottom: 0,
        background: event.color || 'rgba(255, 220, 100, 0.3)',
        pointerEvents: 'none',
        zIndex: 1
      }
    },

    isCellSelected(resourceId, col) {
      if (!this.selectionState || !this.selectionState.active) return false
      if (this.selectionState.resourceId !== resourceId) return false
      const s = Math.min(this.selectionState.startCol, this.selectionState.endCol)
      const e = Math.max(this.selectionState.startCol, this.selectionState.endCol)
      return col >= s && col <= e
    },

    isDraggingEvent(eventId) {
      return this.dragState && this.dragState.eventId === eventId
    },

    isEventInteractive(event) {
      if (!this.interactive) return false
      return this.canEditEvent ? !!this.canEditEvent(event) : true
    }
  }
}
</script>

<style lang="scss">
$resource-width: 180px;
$day-width: 40px;
$header-height: 48px;

.calendar-wrapper {
  overflow-x: auto;
  // Own vertical scroll so the sticky header sticks within it. Inside a .page-fill
  // view flex sizes it to the viewport; the max-height is the fallback elsewhere.
  overflow-y: auto;
  flex: 1 1 auto;
  min-height: 0;
  max-height: calc(100vh - 200px);
  border-top: 1px solid var(--colorRule);
  position: relative;
  background: #fff;
}

.calendar-grid-inner {
  position: relative;
  min-width: $resource-width + $day-width * 31;
  width: max-content;
}

.now-line {
  position: absolute;
  top: 0;
  bottom: 0;
  width: 2px;
  background: var(--colorPrimary);
  z-index: 25;
  pointer-events: none;

  &::before {
    content: '';
    position: absolute;
    top: 0;
    left: -3px;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--colorPrimary);
  }
}

.calendar-sticky-head {
  position: sticky;
  top: 0;
  z-index: 20;
  background: #fff;
}

.calendar-week-row {
  display: flex;
  min-width: $resource-width + $day-width * 31;
  border-bottom: 1px solid var(--colorRule);
}

.week-corner {
  height: 30px;
}

// Mirrors the weekly capacity metric on the dashboard: percentage plus a bar on the same scale
.week-header-cell {
  flex-shrink: 0;
  height: 30px;
  padding: 3px 6px;
  font-size: 11px;
  font-weight: 600;
  color: var(--colorInkStrong);
  border-right: 2px solid var(--colorRule);
  overflow: hidden;

  // a lighter shade of the bar colour; info is the primary blue in this theme
  &--success { background: rgba(var(--colorSuccessRgb), 0.15); }
  &--info { background: rgba(var(--colorPrimaryRgb), 0.15); }
  &--warning { background: rgba(var(--colorYellowRgb), 0.2); }
  &--danger { background: rgba(var(--colorRedRgb), 0.15); }
}

.week-header-label {
  line-height: 14px;
  white-space: nowrap;
}

.week-header-bar {
  height: 7px;
  margin-top: 3px;
  border-radius: 4px;
  background: rgba(255, 255, 255, 0.7);
  overflow: hidden;

  > div {
    height: 100%;
    border-radius: 4px;
  }
}

.calendar-header-row {
  display: flex;
  background: #fff;
  border-bottom: 1px solid var(--colorRule);
  min-width: $resource-width + $day-width * 31;
}

.day-header-cell {
  width: $day-width;
  min-width: $day-width;
  text-align: center;
  padding: 4px 2px;
  border-right: 1px solid var(--colorRule);
  flex-shrink: 0;

  .day-number {
    font-size: 13px;
    font-weight: 600;
    line-height: 1.2;
    color: var(--colorInkStrong);
  }

  .day-name {
    font-size: 10px;
    color: var(--colorInkMuted);
    text-transform: uppercase;
    line-height: 1;
  }

  &.is-today {
    background: rgba(var(--colorPrimaryRgb), 0.12);

    .day-number { color: var(--colorPrimary); }
  }

  &.is-holiday {
    background: rgba(231, 120, 131, 0.25);

    .day-number { color: #e77883; }
    .day-name { color: #e77883; }
  }
}

.resource-label {
  position: sticky;
  left: 0;
  z-index: 10;
  width: $resource-width;
  min-width: $resource-width;
  background: #fff;
  border-right: 1px solid var(--colorRule);
  padding: 0 10px;
  display: flex;
  align-items: center;
  flex-shrink: 0;

  &.header-corner {
    z-index: 30;
    font-weight: 600;
    font-size: 12px;
    text-transform: uppercase;
    color: var(--colorInkMuted);
    height: $header-height;
  }

  &.resource-row-label {
    border-bottom: 1px solid var(--colorRule);
  }

  .resource-note {
    display: block;
    font-weight: 400;
    color: var(--colorInkMuted);
  }

  .resource-name {
    font-size: 13px;
    font-weight: 600;
    color: var(--colorInkStrong);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
}

.resource-row {
  display: flex;
  position: relative;
  border-bottom: 1px solid var(--colorRule);
  min-width: $resource-width + $day-width * 31;

  &:last-child {
    border-bottom: none;
  }
}

.day-header-cell.is-month-start,
.day-cell.is-month-start {
  border-left: 2px solid var(--colorInkMuted);
}

.date-marker {
  position: absolute;
  top: 0;
  bottom: 0;
  width: 4px;
  margin-left: -1px;
  background: #dc3545;
  z-index: 24;
  pointer-events: none;
}

.calendar-month-row {
  display: flex;
  min-width: $resource-width + $day-width * 31;
  border-bottom: 1px solid var(--colorRule);
}

.month-corner {
  height: 24px;
}

.month-header-cell {
  flex-shrink: 0;
  height: 24px;
  line-height: 24px;
  padding: 0 8px;
  font-size: 12px;
  font-weight: 600;
  color: var(--colorInkStrong);
  white-space: nowrap;
  overflow: hidden;
  border-right: 2px solid var(--colorInkMuted);
}

.day-cells {
  display: flex;
  flex: 1;
}

.day-cell {
  width: $day-width;
  min-width: $day-width;
  height: 100%;
  border-right: 1px solid var(--colorRule);
  cursor: crosshair;
  flex-shrink: 0;

  &.is-selected {
    background: rgba(var(--colorPrimaryRgb), 0.12);
  }

  &.is-today {
    background: rgba(var(--colorPrimaryRgb), 0.05);
  }

  &.is-holiday {
    background: rgba(231, 120, 131, 0.16);
  }

  &.is-readonly {
    cursor: default;
  }
}

.background-event {
  border-radius: 0;
}
</style>
