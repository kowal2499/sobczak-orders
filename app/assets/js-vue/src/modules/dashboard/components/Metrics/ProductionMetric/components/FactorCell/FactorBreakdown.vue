<script>
import { defineComponent } from 'vue'
import { getFactorName, getFactorValue } from '../../../../../services/FactorHelper'

/**
 * Składowe współczynnika w popoverze komórki - po jednym pasku na wpis stosu plus wiersz finalny.
 * Szerokości pasków są skalowane do największej wartości bezwzględnej w stosie.
 */
const BAR_COLORS = {
    agreement_line: '#a0a4a8',
    factor_adjustment_ratio: '#f472a0',
    bonus: '#2ba7c4',
    penalty: '#f472a0',
}

export default defineComponent({
    name: 'FactorBreakdown',
    props: {
        factorData: {
            type: Object,
            required: true,
        },
    },
    computed: {
        finalValue() {
            return this.factorData.factor || 0
        },
        scale() {
            const stack = this.factorData.factorsStack || []

            return Math.max(...stack.map(i => Math.abs(i.value)), Math.abs(this.finalValue), 0.0001)
        },
        rows() {
            return (this.factorData.factorsStack || []).map(item => ({
                key: `${item.source}-${item.value}-${item.description || ''}`,
                label: this.rowLabel(item),
                value: getFactorValue(item.source, item.value),
                negative: item.value < 0,
                width: (Math.abs(item.value) / this.scale) * 100,
                color: this.barColor(item),
            }))
        },
        finalBarWidth() {
            return (Math.abs(this.finalValue) / this.scale) * 100
        },
        displayFinalValue() {
            return Math.round(this.finalValue * 100) / 100
        },
    },
    methods: {
        rowLabel(item) {
            const name = getFactorName(item.source, item.value)

            return item.description ? `${name} · ${item.description}` : name
        },
        barColor(item) {
            if (item.source === 'factor_adjustment_bonus'
                || item.source === 'factor_adjustment_bonus_completed_tasks') {
                return item.value < 0 ? BAR_COLORS.penalty : BAR_COLORS.bonus
            }

            return BAR_COLORS[item.source] || BAR_COLORS.agreement_line
        },
    }
})
</script>

<template>
    <div>
        <div class="pop-subtitle">{{ $t('dashboard.factorBreakdown.title') }}</div>

        <div v-for="row in rows" :key="row.key" class="pop-bar-row">
            <div class="d-flex justify-content-between">
                <span>{{ row.label }}</span>
                <span :class="row.negative ? 'text-danger' : 'text-info'">{{ row.value }}</span>
            </div>
            <div class="pop-bar">
                <div :style="{ width: row.width + '%', backgroundColor: row.color }"></div>
            </div>
        </div>

        <div class="pop-bar-row pop-bar-row--final">
            <div class="d-flex justify-content-between font-weight-bold">
                <span>{{ $t('dashboard.factorBreakdown.finalValue') }}</span>
                <span>{{ displayFinalValue }}</span>
            </div>
            <div class="pop-bar">
                <div :style="{ width: finalBarWidth + '%', backgroundColor: '#212529' }"></div>
            </div>
        </div>
    </div>
</template>

<style lang="scss">
.factor-cell-popover {
    .pop-bar-row {
        margin-bottom: 0.6rem;

        &--final {
            margin-top: 0.75rem;
        }
    }
    .pop-bar {
        height: 6px;
        margin-top: 0.2rem;
        background: #f1f3f5;

        div {
            height: 100%;
        }
    }
}
</style>
