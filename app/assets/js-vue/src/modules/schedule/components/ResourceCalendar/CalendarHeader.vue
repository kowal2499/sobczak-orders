<template>
  <div class="calendar-header d-flex align-items-center py-2 px-3 border-bottom bg-light">
    <b-button variant="outline-secondary" size="sm" @click="$emit('prev')">&lsaquo;</b-button>
    <span class="mx-3 h5 mb-0 text-capitalize">{{ label }}</span>
    <b-button variant="outline-secondary" size="sm" @click="$emit('next')">&rsaquo;</b-button>
    <b-button
      v-if="!isCurrentMonth"
      variant="outline-primary"
      size="sm"
      class="ml-3"
      @click="$emit('today')"
    >
      {{ $t('schedule.today') }}
    </b-button>
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
    isCurrentMonth() {
      return moment(this.currentMonth, 'YYYY-MM').isSame(moment(), 'month')
    },
    label() {
      const m = moment(this.currentMonth, 'YYYY-MM')
      return `${this.$t(`schedule.month.${m.month()}`)} ${m.year()}`
    },
    todayLabel() {
      const m = moment()
      const weekday = this.$t(`schedule.dayFull.${m.day()}`)
      const month = this.$t(`schedule.month.${m.month()}`)
      return `${weekday}, ${m.date()} ${month} ${m.year()}`
    }
  }
}
</script>
