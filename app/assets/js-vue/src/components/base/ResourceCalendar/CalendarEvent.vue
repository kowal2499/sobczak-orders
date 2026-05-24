<template>
  <div
    class="calendar-event"
    :class="{ 'is-dragging': isDragging, 'is-readonly': !interactive }"
    :style="eventStyle"
    :title="event.orderName"
  >
    <div class="event-inner">
      <slot :event="event">
        <span class="event-label">{{ event.orderName }}</span>
      </slot>
    </div>
    <div
      v-if="interactive"
      class="resize-handle"
      @mousedown.stop.prevent="$emit('resize-mousedown', $event)"
    />
  </div>
</template>

<script>
import { eventPositionStyle, eventColors } from './utils/gridHelpers'

export default {
  name: 'CalendarEvent',
  props: {
    event: { type: Object, required: true },
    isDragging: { type: Boolean, default: false },
    interactive: { type: Boolean, default: true }
  },
  computed: {
    eventStyle() {
      const pos = eventPositionStyle(this.event)
      const colors = eventColors(this.event)
      return {
        ...pos,
        background: colors.background,
        borderLeftColor: colors.borderColor,
        color: colors.color,
        opacity: this.isDragging ? 0.7 : 1,
        paddingRight: this.interactive ? '20px' : '6px'
      }
    }
  }
}
</script>

<style lang="scss">
.calendar-event {
  position: absolute;
  height: 24px;
  border-radius: 3px;
  border-left: 3px solid transparent;
  padding: 0 20px 0 6px;
  font-size: 12px;
  line-height: 24px;
  display: flex;
  align-items: center;
  cursor: grab;
  user-select: none;
  overflow: hidden;
  z-index: 5;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.15);
  transition: box-shadow 0.1s;

  &:hover {
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.25);
    z-index: 6;
  }

  &.is-dragging {
    cursor: grabbing;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    z-index: 50;
  }

  &.is-readonly {
    cursor: default;
  }

  .event-inner {
    flex: 1;
    overflow: hidden;
    display: flex;
    align-items: center;
    min-width: 0;
  }

  .event-label {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    display: block;
    width: 100%;
  }

  .resize-handle {
    position: absolute;
    right: 0;
    top: 0;
    width: 8px;
    height: 100%;
    cursor: ew-resize;
    background: rgba(0, 0, 0, 0.08);
    border-radius: 0 3px 3px 0;

    &:hover {
      background: rgba(0, 0, 0, 0.2);
    }
  }
}
</style>
