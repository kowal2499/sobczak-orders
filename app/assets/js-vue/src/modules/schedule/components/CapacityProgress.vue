<script>
export default {
    name: "CapacityProgress",
    props: {
        capacity: {
            type: Number,
        },
        capacityBurned: {
            type: Number,
        }
    },

    computed: {
        usagePercent() {
            if (!this.capacity || this.capacity <= 0) {
                return 0
            }
            return Math.min(Math.round((this.capacityBurned / this.capacity) * 100), 100)
        },
        capacityVariant() {
            if (this.usagePercent <= 25) { return 'success' }
            if (this.usagePercent <= 50) { return 'info' }
            if (this.usagePercent <= 75) { return 'warning' }
            return 'danger'
        },
    }
}
</script>

<template>
    <div class="d-flex align-items-center gap-1 text-muted w-100" style="font-size: 0.675rem">
        {{ usagePercent.toFixed(0) }}%
        <b-progress
            :max="100"
            height="10px"
            style="background-color: rgba(216, 216, 216, 0.4);"
            class="w-100 border-color-danger"
        >
            <b-progress-bar :value="usagePercent" :variant="capacityVariant" />
        </b-progress>
    </div>
</template>

<style scoped lang="scss">
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