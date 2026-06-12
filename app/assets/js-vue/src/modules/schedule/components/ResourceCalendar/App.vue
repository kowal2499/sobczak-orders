<template>
  <div id="app" class="container-fluid py-3">
    <h4 class="mb-3">Kalendarz zasobów — planowanie produkcji</h4>


  </div>
</template>

<script>
import ResourceCalendar from './components/ResourceCalendar/ResourceCalendar.vue'
import moment from 'moment'

const m = (offset) => moment().startOf('month').add(offset, 'days').format('YYYY-MM-DD')

export default {
  name: 'App',
  // components: { ResourceCalendar },

}
</script>
