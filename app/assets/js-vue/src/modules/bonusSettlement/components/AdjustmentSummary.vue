<script>
import { defineComponent } from 'vue'

/**
 * Co się złożyło na wartość obowiązującą wiersza. Sekcja jest niezależna od tego, czy okres
 * da się jeszcze edytować - w zamkniętym miesiącu to jedyna treść popovera i często jedyny
 * ślad, dlaczego komuś obcięto premię.
 */
export default defineComponent({
    name: 'AdjustmentSummary',
    props: {
        entry: {
            type: Object,
            required: true,
        },
    },
    computed: {
        isAdjusted() {
            return this.entry.factorsAdjusted !== null
        },
    },
})
</script>

<template>
    <div>
        <div class="pop-row">
            <span class="pop-label">{{ $t('bonus_settlement.col.calculated') }}</span>
            <span class="pop-val">{{ entry.factorsCalculated }}</span>
        </div>

        <template v-if="isAdjusted">
            <div class="pop-row">
                <span class="pop-label">{{ $t('bonus_settlement.adjustment.title') }}</span>
                <span class="pop-val">{{ entry.factorsAdjusted }}</span>
            </div>
            <div class="pop-row" v-if="entry.adjustedAt">
                <span class="pop-label">{{ $t('bonus_settlement.adjustment.savedAt') }}</span>
                <span class="pop-val">{{ entry.adjustedAt }}</span>
            </div>
            <div class="pop-note mt-2" v-if="entry.note">{{ entry.note }}</div>
            <div class="pop-note pop-note--stale mt-2" v-if="entry.adjustmentStale">
                {{ $t('bonus_settlement.adjustment.stale') }}
            </div>
        </template>
        <div class="pop-note mt-2" v-else>{{ $t('bonus_settlement.adjustment.none') }}</div>
    </div>
</template>
