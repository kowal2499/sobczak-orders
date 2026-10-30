<template>
  <div class="calendar-header d-flex align-items-center">
    <b-button variant="outline-secondary" size="sm" :aria-label="$t('_previousMonth')" @click="$emit('prev')">
      <font-awesome-icon icon="chevron-left" />
    </b-button>
    <span class="calendar-header__month">{{ label }}</span>
    <b-button variant="outline-secondary" size="sm" :aria-label="$t('_nextMonth')" @click="$emit('next')">
      <font-awesome-icon icon="chevron-right" />
    </b-button>
    <b-button
      v-if="!isCurrentMonth"
      variant="outline-primary"
      size="sm"
      class="ml-3"
      @click="$emit('today')"
    >
      {{ $t('schedule.today') }}
    </b-button>
    <span class="calendar-header__today">{{ todayLabel }}</span>
  </div>
</template>

<style lang="scss" scoped>
.calendar-header {
  margin-bottom: 0.75rem;

  &__month {
    margin: 0 1rem;
    min-width: 9rem;
    text-align: center;
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--colorInkStrong);
    text-transform: capitalize;
  }

  &__today {
    margin-left: auto;
    color: var(--colorSecondary);
  }
}
</style>

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
