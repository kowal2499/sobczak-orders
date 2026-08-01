<script>
import { defineComponent } from 'vue'

// Odpowiednik DepartmentsBonusOnTimeMetricStrategy::DEFAULT_TOLERANCE_DAYS — wartość obowiązująca
// dla rozliczeń trzyma backend, tu jest tylko po to, żeby pole pokazało ją przed pierwszą zmianą.
const DEFAULT_TOLERANCE_DAYS = 5
const MAX_TOLERANCE_DAYS = 30

/**
 * Pole widełek terminowości (dni robocze) dla miernika premii "w terminie".
 * Zmiana jest wyłącznie podglądem — nie zapisuje się w konfiguracji firmowej.
 */
export default defineComponent({
    name: 'OnTimeToleranceInput',
    props: {
        // null = wartość obowiązująca z backendu
        value: { type: Number, default: null },
    },
    computed: {
        maxTolerance: () => MAX_TOLERANCE_DAYS,
        currentValue() {
            return this.value === null ? DEFAULT_TOLERANCE_DAYS : this.value
        },
    },
    methods: {
        onInput(raw) {
            const days = Math.max(0, Math.min(MAX_TOLERANCE_DAYS, Number(raw)))
            if (Number.isNaN(days) || days === this.currentValue) {
                return
            }
            this.$emit('change', days)
        },
    },
})
</script>

<template>
    <div class="tolerance-row" :title="$t('dashboard.tolerance.hint')">
        <span>{{ $t('dashboard.tolerance.label') }}</span>
        <input
            type="number"
            min="0"
            :max="maxTolerance"
            step="1"
            class="form-control form-control-sm tolerance-input"
            :value="currentValue"
            @change="onInput($event.target.value)"
        >
        <span>{{ $t('dashboard.tolerance.unit') }}</span>
    </div>
</template>

<style scoped lang="scss">
.tolerance-row {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.75rem;
    color: #858796;
    white-space: nowrap;
}

.tolerance-input {
    width: 3.5rem;
    height: auto;
    padding: 0.1rem 0.3rem;
    font-size: 0.75rem;
}
</style>
