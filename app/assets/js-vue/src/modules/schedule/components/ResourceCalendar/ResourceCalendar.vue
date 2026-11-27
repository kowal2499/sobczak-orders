<template>
  <div class="resource-calendar">
    <CalendarHeader
      v-if="showHeader && !range"
      :current-month="currentMonth"
      @prev="prevMonth"
      @next="nextMonth"
      @today="goToday"
    />

    <CalendarGrid
      :resources="resources"
      :columns="columns"
      :positioned-events="positionedEvents"
      :selection-state="selectionState"
      :drag-state="dragState"
      :cell-title="cellTitle"
      :week-header="weekHeader"
      :show-months="!!range"
      :markers="markers"
      :interactive="interactive"
      :holidays="holidays"
      :can-edit-event="canEditEvent"
      v-bind="gridSlots"
      @cell-mousedown="onCellMousedown"
      @cell-mouseenter="onCellMouseenter"
      @cell-mouseup="onCellMouseup"
      @event-mousedown="onEventMousedown"
      @resize-mousedown="onResizeEndMousedown"
      @resize-start-mousedown="onResizeStartMousedown"
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
import { assignLanes, DAY_WIDTH } from './utils/gridHelpers'
import { buildColumns, clipToRange, clampResizeOffset, shiftDate } from './utils/timeline'

export default {
  name: 'ResourceCalendar',
  components: { CalendarHeader, CalendarGrid, NewEventModal },
  props: {
    resources: { type: Array, required: true },
    events: { type: Array, default: () => [] },
    holidays: { type: Array, default: () => [] },
    value: { type: String, default: null },
    interactive: { type: Boolean, default: true },
    allowCrossResource: { type: Boolean, default: false },
    allowCreate: { type: Boolean, default: true },
    canEditEvent: { type: Function, default: null },
    range: { type: Object, default: null },
    showHeader: { type: Boolean, default: true },
    cellTitle: { type: Function, default: null },
    weekHeader: { type: Function, default: null },
    markers: { type: Array, default: () => [] }
  },
  data() {
    return {
      currentMonth: this.value || moment().format('YYYY-MM'),
      localEvents: [...(this.events || [])],
      dragState: null,
      selectionState: null,
      pendingNewEvent: null,
      showNewEventModal: false,
      suppressClick: false
    }
  },
  computed: {
    currentMonthMoment() {
      return moment(this.currentMonth, 'YYYY-MM')
    },
    effectiveRange() {
      if (this.range) return this.range
      return {
        start: this.currentMonthMoment.clone().startOf('month').format('YYYY-MM-DD'),
        end: this.currentMonthMoment.clone().endOf('month').format('YYYY-MM-DD')
      }
    },
    columns() {
      return buildColumns(this.effectiveRange.start, this.effectiveRange.end)
    },
    visibleEvents() {
      return this.localEvents
        .map(e => this.placeEvent(e, e.dateStart, e.dateEnd))
        .filter(Boolean)
    },
    positionedEvents() {
      const result = {}
      this.resources.forEach(r => {
        let rEvents = this.visibleEvents.filter(e => e.resourceId === r.id)

        if (this.dragState) {
          rEvents = rEvents
            .map(e => (e.id === this.dragState.eventId ? this.dragPreview(e) : e))
            .filter(Boolean)

          if (this.dragState.type === 'move' && this.dragState.currentResourceId !== r.id) {
            rEvents = rEvents.filter(e => e.id !== this.dragState.eventId)
          }
          if (this.dragState.type === 'move' && this.dragState.currentResourceId === r.id) {
            const dragged = this.localEvents.find(e => e.id === this.dragState.eventId)
            if (dragged && dragged.resourceId !== r.id) {
              const preview = this.dragPreview(dragged)
              if (preview) rEvents.push(preview)
            }
          }
        }

        rEvents.sort((a, b) => a.startCol - b.startCol)
        result[r.id] = { events: assignLanes(rEvents) }
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
    placeEvent(event, dateStart, dateEnd) {
      const clip = clipToRange(dateStart, dateEnd, this.effectiveRange.start, this.effectiveRange.end)
      return clip ? { ...event, ...clip } : null
    },
    dragPreview(event) {
      const { type, currentDayOffset, currentStartDayOffset, currentEndDayOffset } = this.dragState
      if (type === 'move') {
        const offset = currentDayOffset || 0
        return this.placeEvent(event, shiftDate(event.dateStart, offset), shiftDate(event.dateEnd, offset))
      }
      if (type === 'resize') {
        return this.placeEvent(event, event.dateStart, shiftDate(event.dateEnd, currentEndDayOffset || 0))
      }
      if (type === 'resize-start') {
        return this.placeEvent(event, shiftDate(event.dateStart, currentStartDayOffset || 0), event.dateEnd)
      }
      return this.placeEvent(event, event.dateStart, event.dateEnd)
    },
    prevMonth() {
      this.currentMonth = moment(this.currentMonth, 'YYYY-MM').subtract(1, 'month').format('YYYY-MM')
      this.$emit('month-change', this.currentMonth)
    },
    nextMonth() {
      this.currentMonth = moment(this.currentMonth, 'YYYY-MM').add(1, 'month').format('YYYY-MM')
      this.$emit('month-change', this.currentMonth)
    },
    goToday() {
      const thisMonth = moment().format('YYYY-MM')
      if (this.currentMonth === thisMonth) return
      this.currentMonth = thisMonth
      this.$emit('month-change', this.currentMonth)
    },

    isEventEditable(event) {
      if (!this.interactive) return false
      return this.canEditEvent ? !!this.canEditEvent(event) : true
    },

    onEventMousedown(event, nativeEvent) {
      this.suppressClick = false
      if (!this.isEventEditable(event) || nativeEvent.button !== 0) return
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
    onResizeEndMousedown(event, nativeEvent) {
      this.startResize('resize', event, nativeEvent)
    },
    onResizeStartMousedown(event, nativeEvent) {
      this.startResize('resize-start', event, nativeEvent)
    },
    startResize(type, event, nativeEvent) {
      this.suppressClick = false
      if (!this.isEventEditable(event) || nativeEvent.button !== 0) return
      this.dragState = {
        type,
        eventId: event.id,
        originalEvent: { ...event },
        startMouseX: nativeEvent.clientX,
        currentStartDayOffset: 0,
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

      if (this.dragState.type === 'resize' || this.dragState.type === 'resize-start') {
        const { dateStart, dateEnd } = this.dragState.originalEvent
        const isStart = this.dragState.type === 'resize-start'
        const offset = clampResizeOffset(isStart ? 'start' : 'end', dateStart, dateEnd, Math.round(deltaX / DAY_WIDTH))
        this.dragState = {
          ...this.dragState,
          [isStart ? 'currentStartDayOffset' : 'currentEndDayOffset']: offset
        }
      }
    },
    onGlobalMouseup() {
      if (!this.dragState) return
      const {
        type, eventId, originalEvent, currentDayOffset, currentResourceId, currentStartDayOffset, currentEndDayOffset
      } = this.dragState
      this.dragState = null

      const eventIndex = this.localEvents.findIndex(e => e.id === eventId)
      if (eventIndex === -1) return
      const ev = { ...this.localEvents[eventIndex] }

      if (type === 'move') {
        const offset = currentDayOffset || 0
        if (offset !== 0 || currentResourceId !== originalEvent.resourceId) {
          this.suppressClick = true
        }
        ev.dateStart = shiftDate(ev.dateStart, offset)
        ev.dateEnd = shiftDate(ev.dateEnd, offset)
        ev.resourceId = currentResourceId
        this.$set(this.localEvents, eventIndex, ev)
        this.$emit('event-moved', { previous: originalEvent, updated: ev })
      }

      if (type === 'resize') {
        const offset = currentEndDayOffset || 0
        if (offset !== 0) {
          this.suppressClick = true
          ev.dateEnd = shiftDate(ev.dateEnd, offset)
          this.$set(this.localEvents, eventIndex, ev)
          this.$emit('event-resized', { previous: originalEvent, updated: ev })
        }
      }

      if (type === 'resize-start') {
        const offset = currentStartDayOffset || 0
        if (offset !== 0) {
          this.suppressClick = true
          ev.dateStart = shiftDate(ev.dateStart, offset)
          this.$set(this.localEvents, eventIndex, ev)
          this.$emit('event-resized', { previous: originalEvent, updated: ev })
        }
      }
    },

    onEventClick(event) {
      if (this.suppressClick) {
        this.suppressClick = false
        return
      }
      this.$emit('event-click', event)
    },

    onCellMousedown(resourceId, col) {
      if (!this.interactive || !this.allowCreate) return
      this.selectionState = { resourceId, startCol: col, endCol: col, active: true }
    },
    onCellMouseenter(resourceId, col) {
      if (!this.selectionState || !this.selectionState.active) return
      if (this.selectionState.resourceId !== resourceId) return
      this.selectionState = { ...this.selectionState, endCol: col }
    },
    onCellMouseup() {
      if (!this.selectionState) return
      const { resourceId: rId, startCol, endCol } = this.selectionState
      this.selectionState = null

      const resource = this.resources.find(r => r.id === rId)

      this.pendingNewEvent = {
        resourceId: rId,
        resourceName: resource ? resource.name : '',
        dateStart: this.columns[Math.min(startCol, endCol)].format('YYYY-MM-DD'),
        dateEnd: this.columns[Math.max(startCol, endCol)].format('YYYY-MM-DD')
      }
      this.showNewEventModal = true
    },

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
  display: flex;
  flex-direction: column;
  flex: 1 1 auto;
  min-height: 0;
}
</style>
