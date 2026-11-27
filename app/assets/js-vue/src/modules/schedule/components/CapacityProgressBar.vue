<script>
import { capacityPercent, capacityVariant } from '../services/capacityScale'

export default {
    name: "CapacityProgressBar",
    props: {
        capacity: {
            type: Number,
            default: 0
        },
        capacityBurned: {
            type: Number,
            default: 0
        },
        capacityBurnedForecast: {
            type: Number,
            default: 0
        }
    },
    computed: {
        percent() {
            return capacityPercent(this.capacity, this.capacityBurned)
        },
        forecastPercent() {
            return capacityPercent(this.capacity, this.capacityBurnedForecast)
        },
        totalPercent() {
            return this.percent + this.forecastPercent
        },
        variant() {
            return capacityVariant(this.totalPercent)
        }
    }
}
</script>

<template>
    <div class="d-flex align-items-center gap-1 text-muted w-100" style="font-size: 0.75rem">
        <span>
            {{ percent }}%<span v-if="forecastPercent > 0" class="forecast-label"> + {{ forecastPercent }}%</span>
        </span>
        <b-progress
            :max="100"
            height="15px"
            style="background-color: rgba(216, 216, 216, 0.4);"
            class="w-100"
        >
            <b-progress-bar :value="Math.min(percent, 100)" :variant="variant" />
            <b-progress-bar
                v-if="forecastPercent > 0"
                :value="Math.min(forecastPercent, Math.max(0, 100 - percent))"
                class="forecast-segment"
            />
        </b-progress>
    </div>
</template>

<style scoped lang="scss">
.forecast-label {
    color: #8e44ad;
    font-style: italic;
}
.forecast-segment {
    background-color: #b39ddb !important;
    background-image: repeating-linear-gradient(
        45deg,
        rgba(255, 255, 255, 0.25) 0,
        rgba(255, 255, 255, 0.25) 4px,
        transparent 4px,
        transparent 8px
    );
}
</style>
