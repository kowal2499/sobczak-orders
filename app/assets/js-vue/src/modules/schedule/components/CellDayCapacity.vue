<script>
export default {
    name: "CellDayCapacity",
    props: {
        arg: {
            type: Object,
            default: () => ({})
        },
        events: {
            type: Array,
            default: () => ([])
        },
        allowSelect: {
            type: Boolean,
            default: false,
        }
    },
    methods: {
        getUsagePercent(event) {
            if (!event || !event.capacity || event.capacity <= 0) {
                return 0
            }
            return Math.min(Math.round((event.capacityBurned / event.capacity) * 100), 100)
        },
        getCapacityVariant(percent) {
            if (percent <= 25) { return 'success' }
            if (percent <= 50) { return 'info' }
            if (percent <= 75) { return 'warning' }
            return 'danger'
        },
        onSelectDay(event) {
            if (!event || !this.hasAvailableCapacity(event)) {
                return
            }
            console.log('onSelectDay', event)
            this.$emit('day-selected', event)
        },

        hasAvailableCapacity(event) {
            return event.capacity > 0 && event.capacityBurned < event.capacity
        },
    }
}
</script>

<template>
    <div>
        <div v-for="event in events" class="d-flex flex-column justify-content-center gap-2">
            <div class="d-flex align-items-center gap-1 text-muted w-100" style="font-size: 0.75rem">
                {{ getUsagePercent(event).toFixed(0) }}%
                <b-progress
                    :max="100"
                    height="15px"
                    style="background-color: rgba(216, 216, 216, 0.4);"
                    class="w-100 border-color-danger"
                    :id="event.id"
                >
                    <b-progress-bar :value="getUsagePercent(event)" :variant="getCapacityVariant(getUsagePercent(event))" />
                </b-progress>
                <b-popover
                    :target="event.id"
                    title="Popover!"
                    triggers="hover focus"
                >
                    <pre>{{ event }}</pre>
                </b-popover>
            </div>
            <button
                v-if="allowSelect"
                :class="['btn btn-sm btn-pick-day d-inline-block', `btn-outline-primary`]"
                @click.stop.prevent="onSelectDay(event)"
            >
                Wybierz
            </button>
        </div>
    </div>
</template>

<style lang="scss">
.fc-day:hover {
    .btn-pick-day {
        visibility: visible;
    }
}
.btn-pick-day {
    visibility: hidden;
}

.progress-container {
    width: 100%;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}
.schedule-progress {
    width: 100%;
    height: 6px;
    background-color: #e9ecef;
    border-radius: 3px;
    overflow: hidden;
}

.schedule-progress-bar {
    height: 100%;
    border-radius: 3px;
    transition: width 0.3s ease;
}

.schedule-capacity-label {
    font-size: 0.7rem;
    color: #6c757d;
    line-height: 1;
}
</style>