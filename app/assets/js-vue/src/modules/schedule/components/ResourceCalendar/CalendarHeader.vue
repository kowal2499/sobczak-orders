<template>
  <div class="calendar-header d-flex align-items-center py-2 px-3 border-bottom bg-light">
    <b-button variant="outline-secondary" size="sm" @click="$emit('prev')">&lsaquo;</b-button>
    <span class="mx-3 h5 mb-0 text-capitalize">{{ label }}</span>
    <b-button variant="outline-secondary" size="sm" @click="$emit('next')">&rsaquo;</b-button>
    <span class="ml-auto text-muted small">{{ todayLabel }}</span>
  </div>
</template>

<script>
import moment from 'moment'

export default {
  name: 'CalendarHeader',
  props: {
    currentMonth: { type: String, required: true }
  },
  computed: {
    label() {
      return moment(this.currentMonth, 'YYYY-MM').format('MMMM YYYY')
    },
    todayLabel() {
      return moment().format('dddd, D MMMM YYYY')
    }
  }
}
</script>
