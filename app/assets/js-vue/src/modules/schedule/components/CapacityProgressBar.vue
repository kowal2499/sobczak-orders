<script>
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
        }
    },
    computed: {
        percent() {
            if (!this.capacity || this.capacity <= 0) return 0
            return Math.round((this.capacityBurned / this.capacity) * 100)
        },
        variant() {
            if (this.percent <= 25) return 'success'
            if (this.percent <= 50) return 'info'
            if (this.percent <= 75) return 'warning'
            return 'danger'
        }
    }
}
</script>

<template>
    <div class="d-flex align-items-center gap-1 text-muted w-100" style="font-size: 0.75rem">
        {{ percent }}%
        <b-progress
            :max="100"
            height="15px"
            style="background-color: rgba(216, 216, 216, 0.4);"
            class="w-100"
        >
            <b-progress-bar :value="Math.min(percent, 100)" :variant="variant" />
        </b-progress>
    </div>
</template>
