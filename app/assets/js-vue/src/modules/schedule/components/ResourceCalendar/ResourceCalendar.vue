<template>
  <div class="resource-calendar">
    <CalendarHeader
      :current-month="currentMonth"
      @prev="prevMonth"
      @next="nextMonth"
    />

    <CalendarGrid
      :resources="resources"
      :days-in-month="daysInMonth"
      :positioned-events="positionedEvents"
      :selection-state="selectionState"
      :drag-state="dragState"
      :current-month-moment="currentMonthMoment"
      :interactive="interactive"
      v-bind="gridSlots"
      @cell-mousedown="onCellMousedown"
      @cell-mouseenter="onCellMouseenter"
      @cell-mouseup="onCellMouseup"
      @event-mousedown="onEventMousedown"
      @resize-mousedown="onResizeMousedown"
      @event-click="onEventClick"
    >
      <!-- Forward all slots from ResourceCalendar down to CalendarGrid -->
      <template v-for="(_, name) in $slots" v-slot:[name]="slotProps">
        <slot :name="name" v-bind="slotProps || {}" />
      </template>
      <template v-for="(_, name) in $scopedSlots" v-slot:[name]="slotProps">
        <slot :name="name" v-bind="slotProps || {}" />
      </template>
    </CalendarGrid>

    <NewEventModal
      :visible="showNewEventModal"
      :prefill="pendingNewEvent"
      @save="onNewEventSave"
      @cancel="onNewEventCancel"
    />
  </div>
</template>

<script>
import moment from 'moment'
import CalendarHeader from './CalendarHeader.vue'
import CalendarGrid from './CalendarGrid.vue'
import NewEventModal from './NewEventModal.vue'
import { assignLanes, DAY_WIDTH, RESOURCE_WIDTH, EVENT_HEIGHT } from './utils/gridHelpers'

export default {
  name: 'ResourceCalendar',
  components: { CalendarHeader, CalendarGrid, NewEventModal },
  props: {
    resources: { type: Array, required: true },
    events: { type: Array, default: () => [] },
    value: { type: String, default: null },
    interactive: { type: Boolean, default: true },
    allowCrossResource: { type: Boolean, default: false }
  },
  data() {
    return {
      currentMonth: this.value || moment().format('YYYY-MM'),
      localEvents: [...(this.events || [])],
      dragState: null,
      selectionState: null,
      pendingNewEvent: null,
      showNewEventModal: false
    }
  },
  computed: {
    currentMonthMoment() {
      return moment(this.currentMonth, 'YYYY-MM')
    },
    daysInMonth() {
      const count = this.currentMonthMoment.daysInMonth()
      return Array.from({ length: count }, (_, i) => i + 1)
    },
    visibleEvents() {
      const monthStart = this.currentMonthMoment.clone().startOf('month')
      const monthEnd = this.currentMonthMoment.clone().endOf('month')

      return this.localEvents
        .filter(e => {
          const eStart = moment(e.dateStart)
          const eEnd = moment(e.dateEnd)
          return eStart.isSameOrBefore(monthEnd, 'day') && eEnd.isSameOrAfter(monthStart, 'day')
        })
        .map(e => {
          const eStart = moment(e.dateStart)
          const eEnd = moment(e.dateEnd)
          return {
            ...e,
            clippedStart: moment.max(eStart, monthStart),
            clippedEnd: moment.min(eEnd, monthEnd)
          }
        })
    },
    positionedEvents() {
      const result = {}
      this.resources.forEach(r => {
        let rEvents = this.visibleEvents
          .filter(e => e.resourceId === r.id)
          .sort((a, b) => a.clippedStart.diff(b.clippedStart))

        // Apply in-progress drag preview
        if (this.dragState) {
          rEvents = rEvents.map(e => {
            if (e.id !== this.dragState.eventId) return e

            if (this.dragState.type === 'move') {
              const offset = this.dragState.currentDayOffset || 0
              const newStart = moment(e.dateStart).add(offset, 'days')
              const newEnd = moment(e.dateEnd).add(offset, 'days')
              const monthStart = this.currentMonthMoment.clone().startOf('month')
              const monthEnd = this.currentMonthMoment.clone().endOf('month')
              return {
                ...e,
                clippedStart: moment.max(newStart, monthStart),
                clippedEnd: moment.min(newEnd, monthEnd)
              }
            }

            if (this.dragState.type === 'resize') {
              const offset = this.dragState.currentEndDayOffset || 0
              const newEnd = moment(e.dateEnd).add(offset, 'days')
              const monthStart = this.currentMonthMoment.clone().startOf('month')
              const monthEnd = this.currentMonthMoment.clone().endOf('month')
              const minEnd = moment.max(moment(e.dateStart), monthStart)
              return {
                ...e,
                clippedEnd: moment.max(moment.min(newEnd, monthEnd), minEnd)
              }
            }

            return e
          })

          // For move drag: if the event belongs to a different resource now, filter it out or add it
          if (this.dragState.type === 'move' && this.dragState.currentResourceId !== r.id) {
            rEvents = rEvents.filter(e => e.id !== this.dragState.eventId)
          }
          if (this.dragState.type === 'move' && this.dragState.currentResourceId === r.id) {
            const draggedEvent = this.visibleEvents.find(e => e.id === this.dragState.eventId)
            if (draggedEvent && draggedEvent.resourceId !== r.id) {
              const offset = this.dragState.currentDayOffset || 0
              const newStart = moment(draggedEvent.dateStart).add(offset, 'days')
              const newEnd = moment(draggedEvent.dateEnd).add(offset, 'days')
              const monthStart = this.currentMonthMoment.clone().startOf('month')
              const monthEnd = this.currentMonthMoment.clone().endOf('month')
              rEvents.push({
                ...draggedEvent,
                clippedStart: moment.max(newStart, monthStart),
                clippedEnd: moment.min(newEnd, monthEnd)
              })
              rEvents.sort((a, b) => a.clippedStart.diff(b.clippedStart))
            }
          }
        }

        const withLanes = assignLanes(rEvents)
        result[r.id] = { events: withLanes }
      })
      return result
    },
    // Pass slots as a hint to CalendarGrid (used for slot detection)
    gridSlots() {
      return {}
    }
  },
  watch: {
    events(val) {
      this.localEvents = [...val]
    },
    value(val) {
      if (val) this.currentMonth = val
    }
  },
  mounted() {
    document.addEventListener('mousemove', this.onGlobalMousemove)
    document.addEventListener('mouseup', this.onGlobalMouseup)
  },
  beforeDestroy() {
    document.removeEventListener('mousemove', this.onGlobalMousemove)
    document.removeEventListener('mouseup', this.onGlobalMouseup)
  },
  methods: {
    prevMonth() {
      this.currentMonth = moment(this.currentMonth, 'YYYY-MM').subtract(1, 'month').format('YYYY-MM')
      this.$emit('month-change', this.currentMonth)
    },
    nextMonth() {
      this.currentMonth = moment(this.currentMonth, 'YYYY-MM').add(1, 'month').format('YYYY-MM')
      this.$emit('month-change', this.currentMonth)
    },

    // ─── Drag events ────────────────────────────────────────────────
    onEventMousedown(event, nativeEvent) {
      if (!this.interactive || nativeEvent.button !== 0) return
      this.dragState = {
        type: 'move',
        eventId: event.id,
        originalEvent: { ...event },
        startMouseX: nativeEvent.clientX,
        startMouseY: nativeEvent.clientY,
        currentDayOffset: 0,
        currentResourceId: event.resourceId
      }
    },
    onResizeMousedown(event, nativeEvent) {
      if (!this.interactive || nativeEvent.button !== 0) return
      this.dragState = {
        type: 'resize',
        eventId: event.id,
        originalEvent: { ...event },
        startMouseX: nativeEvent.clientX,
        currentEndDayOffset: 0
      }
    },
    onGlobalMousemove(nativeEvent) {
      if (!this.dragState) return
      const deltaX = nativeEvent.clientX - this.dragState.startMouseX

      if (this.dragState.type === 'move') {
        const update = { currentDayOffset: Math.round(deltaX / DAY_WIDTH) }
        if (this.allowCrossResource) {
          const el = document.elementFromPoint(nativeEvent.clientX, nativeEvent.clientY)
          const cell = el && el.closest('[data-resource-id]')
          if (cell && cell.dataset.resourceId) {
            update.currentResourceId = cell.dataset.resourceId
          }
        }
        this.dragState = { ...this.dragState, ...update }
      }

      if (this.dragState.type === 'resize') {
        // Allow shortening: minimum offset keeps dateEnd >= dateStart (min 1-day span)
        const minOffset = moment(this.dragState.originalEvent.dateStart)
          .diff(moment(this.dragState.originalEvent.dateEnd), 'days')
        this.dragState = {
          ...this.dragState,
          currentEndDayOffset: Math.max(minOffset, Math.round(deltaX / DAY_WIDTH))
        }
      }
    },
    onGlobalMouseup() {
      if (!this.dragState) return
      const { type, eventId, originalEvent, currentDayOffset, currentResourceId, currentEndDayOffset } = this.dragState
      this.dragState = null

      const eventIndex = this.localEvents.findIndex(e => e.id === eventId)
      if (eventIndex === -1) return
      const ev = { ...this.localEvents[eventIndex] }

      if (type === 'move') {
        const offset = currentDayOffset || 0
        ev.dateStart = moment(ev.dateStart).add(offset, 'days').format('YYYY-MM-DD')
        ev.dateEnd = moment(ev.dateEnd).add(offset, 'days').format('YYYY-MM-DD')
        ev.resourceId = currentResourceId
        this.$set(this.localEvents, eventIndex, ev)
        this.$emit('event-moved', { previous: originalEvent, updated: ev })
      }

      if (type === 'resize') {
        const offset = currentEndDayOffset || 0
        if (offset !== 0) {
          ev.dateEnd = moment(ev.dateEnd).add(offset, 'days').format('YYYY-MM-DD')
          this.$set(this.localEvents, eventIndex, ev)
          this.$emit('event-resized', { previous: originalEvent, updated: ev })
        }
      }
    },

    onEventClick(event) {
      this.$emit('event-click', event)
    },

    // ─── Cell selection ──────────────────────────────────────────────
    onCellMousedown(resourceId, day) {
      if (!this.interactive) return
      this.selectionState = { resourceId, startDay: day, endDay: day, active: true }
    },
    onCellMouseenter(resourceId, day) {
      if (!this.selectionState || !this.selectionState.active) return
      if (this.selectionState.resourceId !== resourceId) return
      this.selectionState = { ...this.selectionState, endDay: day }
    },
    onCellMouseup(resourceId, day) {
      if (!this.selectionState) return
      const { resourceId: rId, startDay, endDay } = this.selectionState
      this.selectionState = null

      const year = this.currentMonthMoment.year()
      const month = this.currentMonthMoment.month()
      const s = Math.min(startDay, endDay)
      const e = Math.max(startDay, endDay)
      const resource = this.resources.find(r => r.id === rId)

      this.pendingNewEvent = {
        resourceId: rId,
        resourceName: resource ? resource.name : '',
        dateStart: moment([year, month, s]).format('YYYY-MM-DD'),
        dateEnd: moment([year, month, e]).format('YYYY-MM-DD')
      }
      this.showNewEventModal = true
    },

    // ─── New event modal ─────────────────────────────────────────────
    onNewEventSave(eventData) {
      this.localEvents.push(eventData)
      this.showNewEventModal = false
      this.pendingNewEvent = null
      this.$emit('event-created', eventData)
    },
    onNewEventCancel() {
      this.showNewEventModal = false
      this.pendingNewEvent = null
    }
  }
}
</script>

<style lang="scss">
.resource-calendar {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}
</style>
