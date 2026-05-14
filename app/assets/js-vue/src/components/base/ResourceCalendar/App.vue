<template>
  <div id="app" class="container-fluid py-3">
    <h4 class="mb-3">Kalendarz zasobów — planowanie produkcji</h4>

    <ResourceCalendar
      :resources="resources"
      :events="events"
      @event-moved="onEventMoved"
      @event-resized="onEventResized"
      @event-created="onEventCreated"
      @event-click="onEventClick"
    >
      <!-- Slot per typ eventu -->
      <template #event-type-order="{ event }">
        <span class="event-label fw-semibold">📦 {{ event.orderName }}</span>
      </template>
      <template #event-type-maintenance="{ event }">
        <span class="event-label text-danger">🔧 {{ event.orderName }}</span>
      </template>
      <!-- Slot per resource (dpt04 - Lakierowanie ma inny wygląd) -->
      <template #resource-dpt04="{ event }">
        <span class="event-label" style="font-style:italic">🎨 {{ event.orderName }}</span>
      </template>
    </ResourceCalendar>

    <div v-if="lastAction" class="mt-3 alert alert-info small">
      {{ lastAction }}
    </div>
  </div>
</template>

<script>
import ResourceCalendar from './components/ResourceCalendar/ResourceCalendar.vue'
import moment from 'moment'

const m = (offset) => moment().startOf('month').add(offset, 'days').format('YYYY-MM-DD')

export default {
  name: 'App',
  components: { ResourceCalendar },
  data() {
    return {
      lastAction: null,
      resources: [
        { id: 'dpt01', name: 'Klejenie' },
        { id: 'dpt02', name: 'CNC' },
        { id: 'dpt03', name: 'Szlifowanie' },
        { id: 'dpt04', name: 'Lakierowanie' },
        { id: 'dpt05', name: 'Pakowanie' },
        { id: 'dpt06', name: 'INTOREX' }
      ],
      events: [
        // Klejenie — dwa nakładające się zlecenia (test lane packing)
        {
          id: 'e1', resourceId: 'dpt01', resourceName: 'Klejenie',
          orderName: 'Zlecenie #1001', orderStatus: 'in_progress', eventType: 'order',
          dateStart: m(1), dateEnd: m(8)
        },
        {
          id: 'e2', resourceId: 'dpt01', resourceName: 'Klejenie',
          orderName: 'Zlecenie #1002', orderStatus: 'pending', eventType: 'order',
          dateStart: m(4), dateEnd: m(12)
        },
        {
          id: 'e3', resourceId: 'dpt01', resourceName: 'Klejenie',
          orderName: 'Zlecenie #1003', orderStatus: 'completed', eventType: 'order',
          dateStart: m(0), dateEnd: m(5)
        },
        // CNC
        {
          id: 'e4', resourceId: 'dpt02', resourceName: 'CNC',
          orderName: 'Zlecenie #2001', orderStatus: 'in_progress', eventType: 'order',
          dateStart: m(2), dateEnd: m(10)
        },
        {
          id: 'e5', resourceId: 'dpt02', resourceName: 'CNC',
          orderName: 'Serwis maszyny', orderStatus: 'pending', eventType: 'maintenance',
          color: '#fce4ec',
          dateStart: m(14), dateEnd: m(15)
        },
        // Szlifowanie
        {
          id: 'e6', resourceId: 'dpt03', resourceName: 'Szlifowanie',
          orderName: 'Zlecenie #3001', orderStatus: 'pending', eventType: 'order',
          dateStart: m(5), dateEnd: m(18)
        },
        // Lakierowanie (użyje slotu resource-dpt04)
        {
          id: 'e7', resourceId: 'dpt04', resourceName: 'Lakierowanie',
          orderName: 'Zlecenie #4001', orderStatus: 'in_progress', eventType: 'order',
          dateStart: m(0), dateEnd: m(7)
        },
        {
          id: 'e8', resourceId: 'dpt04', resourceName: 'Lakierowanie',
          orderName: 'Zlecenie #4002', orderStatus: 'cancelled', eventType: 'order',
          dateStart: m(9), dateEnd: m(20)
        },
        // Pakowanie
        {
          id: 'e9', resourceId: 'dpt05', resourceName: 'Pakowanie',
          orderName: 'Zlecenie #5001', orderStatus: 'completed', eventType: 'order',
          dateStart: m(3), dateEnd: m(9)
        },
        // INTOREX — background event (np. przerwa technologiczna)
        {
          id: 'e10', resourceId: 'dpt06', resourceName: 'INTOREX',
          orderName: 'Przerwa technologiczna', orderStatus: 'pending', eventType: 'background',
          color: 'rgba(255, 152, 0, 0.25)',
          dateStart: m(10), dateEnd: m(14)
        },
        {
          id: 'e11', resourceId: 'dpt06', resourceName: 'INTOREX',
          orderName: 'Zlecenie #6001', orderStatus: 'in_progress', eventType: 'order',
          dateStart: m(0), dateEnd: m(6)
        }
      ]
    }
  },
  methods: {
    onEventMoved({ previous, updated }) {
      const idx = this.events.findIndex(e => e.id === updated.id)
      if (idx !== -1) this.$set(this.events, idx, updated)
      this.lastAction = `Przeniesiono: ${updated.orderName} → ${updated.resourceId} (${updated.dateStart} – ${updated.dateEnd})`
    },
    onEventResized({ previous, updated }) {
      const idx = this.events.findIndex(e => e.id === updated.id)
      if (idx !== -1) this.$set(this.events, idx, updated)
      this.lastAction = `Zmieniono rozmiar: ${updated.orderName} → do ${updated.dateEnd}`
    },
    onEventCreated(event) {
      this.events.push(event)
      this.lastAction = `Utworzono: ${event.orderName} w ${event.resourceName} (${event.dateStart} – ${event.dateEnd})`
    },
    onEventClick(event) {
      this.lastAction = `Kliknięto: ${event.orderName} [${event.orderStatus}]`
    }
  }
}
</script>
