<template>
  <div class="calendar-wrapper" ref="wrapper">
    <!-- Header row: corner + day numbers -->
    <div class="calendar-header-row">
      <div class="resource-label header-corner">Zasób</div>
      <div
        v-for="col in dayColumns"
        :key="col.day"
        class="day-header-cell"
        :class="{
          'is-today': col.isToday,
          'is-weekend': col.isWeekend
        }"
      >
        <div class="day-number">{{ col.day }}</div>
        <div class="day-name">{{ col.dayName }}</div>
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
          <span class="resource-name">{{ resource.name }}</span>
        </slot>
      </div>

      <!-- Day cells (background grid) -->
      <div class="day-cells">
        <div
          v-for="col in dayColumns"
          :key="col.day"
          class="day-cell"
          :class="{
            'is-selected': isCellSelected(resource.id, col.day),
            'is-weekend': col.isWeekend,
            'is-today': col.isToday,
            'is-readonly': !interactive
          }"
          :data-resource-id="resource.id"
          :data-day="col.day"
          @mousedown.prevent="$emit('cell-mousedown', resource.id, col.day, $event)"
          @mouseenter="$emit('cell-mouseenter', resource.id, col.day)"
          @mouseup="$emit('cell-mouseup', resource.id, col.day)"
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
        :interactive="interactive"
        @mousedown.native.prevent="$emit('event-mousedown', event, $event)"
        @resize-mousedown="$emit('resize-mousedown', event, $event)"
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
</template>

<script>
import moment from 'moment'
import CalendarEvent from './CalendarEvent.vue'
import { isWeekend, isToday } from './utils/dateHelpers'
import { DAY_WIDTH, RESOURCE_WIDTH, EVENT_HEIGHT } from './utils/gridHelpers'

export default {
  name: 'CalendarGrid',
  components: { CalendarEvent },
  props: {
    resources: { type: Array, required: true },
    daysInMonth: { type: Array, required: true },
    positionedEvents: { type: Object, required: true },
    selectionState: { type: Object, default: null },
    dragState: { type: Object, default: null },
    currentMonthMoment: { type: Object, required: true },
    interactive: { type: Boolean, default: true }
  },
  computed: {
    dayColumns() {
      const year = this.currentMonthMoment.year()
      const month = this.currentMonthMoment.month()
      return this.daysInMonth.map(day => ({
        day,
        isWeekend: isWeekend(year, month, day),
        isToday: isToday(year, month, day),
        dayName: this.$t(`schedule.dayAbbr.${moment([year, month, day]).day()}`)
      }))
    }
  },
  methods: {
    rowHeight(resourceId) {
      const data = this.positionedEvents[resourceId]
      if (!data) return 60
      const maxLane = data.events.reduce((max, e) => Math.max(max, e.lane || 0), 0)
      const laneCount = data.events.length > 0 ? maxLane + 1 : 0
      return Math.max(60, laneCount * EVENT_HEIGHT + 10)
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
      const left = RESOURCE_WIDTH + (event.clippedStart.date() - 1) * DAY_WIDTH
      const width = (event.clippedEnd.date() - event.clippedStart.date() + 1) * DAY_WIDTH
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

    isCellSelected(resourceId, day) {
      if (!this.selectionState || !this.selectionState.active) return false
      if (this.selectionState.resourceId !== resourceId) return false
      const s = Math.min(this.selectionState.startDay, this.selectionState.endDay)
      const e = Math.max(this.selectionState.startDay, this.selectionState.endDay)
      return day >= s && day <= e
    },

    isDraggingEvent(eventId) {
      return this.dragState && this.dragState.eventId === eventId
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
  overflow-y: auto;
  max-height: calc(100vh - 120px);
  border: 1px solid #dee2e6;
  border-radius: 4px;
  position: relative;
  background: #fff;
}

.calendar-header-row {
  display: flex;
  position: sticky;
  top: 0;
  z-index: 20;
  background: #f8f9fa;
  border-bottom: 2px solid #dee2e6;
  min-width: $resource-width + $day-width * 31;
}

.day-header-cell {
  width: $day-width;
  min-width: $day-width;
  text-align: center;
  padding: 4px 2px;
  border-right: 1px solid #dee2e6;
  flex-shrink: 0;

  .day-number {
    font-size: 13px;
    font-weight: 600;
    line-height: 1.2;
  }

  .day-name {
    font-size: 10px;
    color: #6c757d;
    text-transform: uppercase;
    line-height: 1;
  }

  &.is-today {
    background: #e3f2fd;

    .day-number { color: #0d6efd; }
  }

  &.is-weekend {
    background: #f5f5f5;

    .day-number { color: #9e9e9e; }
    .day-name { color: #bdbdbd; }
  }
}

.resource-label {
  position: sticky;
  left: 0;
  z-index: 10;
  width: $resource-width;
  min-width: $resource-width;
  background: #fff;
  border-right: 2px solid #dee2e6;
  padding: 0 10px;
  display: flex;
  align-items: center;
  flex-shrink: 0;

  &.header-corner {
    z-index: 30;
    background: #f8f9fa;
    font-weight: 700;
    font-size: 12px;
    text-transform: uppercase;
    color: #6c757d;
    height: $header-height;
  }

  &.resource-row-label {
    border-bottom: 1px solid #dee2e6;
  }

  .resource-name {
    font-size: 13px;
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
}

.resource-row {
  display: flex;
  position: relative;
  border-bottom: 1px solid #dee2e6;
  min-width: $resource-width + $day-width * 31;

  &:last-child {
    border-bottom: none;
  }
}

.day-cells {
  display: flex;
  flex: 1;
}

.day-cell {
  width: $day-width;
  min-width: $day-width;
  height: 100%;
  border-right: 1px solid #f0f0f0;
  cursor: crosshair;
  flex-shrink: 0;

  &.is-selected {
    background: rgba(13, 110, 253, 0.12);
  }

  &.is-weekend {
    background: #fafafa;
  }

  &.is-today {
    background: rgba(13, 110, 253, 0.05);
  }

  &.is-readonly {
    cursor: default;
  }
}

.background-event {
  border-radius: 0;
  opacity: 0.5;
}
</style>
