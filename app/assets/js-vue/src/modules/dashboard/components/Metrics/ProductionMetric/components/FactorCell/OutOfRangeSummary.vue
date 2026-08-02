<script>
import { defineComponent } from 'vue'
import { MONTHS } from '@/services/datesService'
import { fmtDate, fmtDayMonth } from './dates'

/**
 * Sekcja popovera dla pozycji, która nie kwalifikuje się do bieżącego zakresu raportu.
 *
 * O kwalifikacji decyduje data ukończenia (patrz AbstractProductionRecordStrategy::qualifies),
 * a nie zaplanowane okno - dlatego powód podajemy datą ukończenia albo jej brakiem. Okno zostaje
 * w dymku wyłącznie jako kontekst: potrafi leżeć w całości w zakresie raportu, a pozycja i tak
 * nie jest liczona, bo zadanie skończono w innym miesiącu.
 */
export default defineComponent({
    name: 'OutOfRangeSummary',
    props: {
        production: {
            type: Object,
            required: true,
        },
        // { start: 'YYYY-MM-DD', end: 'YYYY-MM-DD' } - zakres raportu (miesiąc z pulpitu)
        reportRange: {
            type: Object,
            default: () => ({ start: null, end: null })
        },
    },
    computed: {
        isCompleted() {
            return !!this.production.completedAt
        },
        completedAtLabel() {
            return this.isCompleted
                ? fmtDate(this.production.completedAt)
                : this.$t('dashboard.onTimeCell.outOfRange.notCompleted')
        },
        reasonNote() {
            return this.isCompleted
                ? this.$t('dashboard.onTimeCell.outOfRange.noteCompletedOutside')
                : this.$t('dashboard.onTimeCell.outOfRange.noteNotCompleted')
        },
        reportRangeLabel() {
            return this.rangeLabel(this.reportRange.start, this.reportRange.end)
        },
        productionWindowLabel() {
            return this.rangeLabel(this.production.dateStart, this.production.dateEnd)
        },
    },
    methods: {
        // "lipiec 2026 (01.07 – 31.07)"
        rangeLabel(start, end) {
            if (!start || !end) {
                return null
            }
            const [year, month] = String(start).slice(0, 10).split('-')
            const monthName = this.$t(MONTHS[Number(month) - 1].name).toLowerCase()

            return `${monthName} ${year} (${fmtDayMonth(start)} – ${fmtDayMonth(end)})`
        },
    }
})
</script>

<template>
    <div>
        <div class="pop-title">{{ $t('dashboard.onTimeCell.outOfRange.title') }}</div>
        <div class="pop-row">
            <span class="pop-label">{{ $t('dashboard.onTimeCell.outOfRange.reportRange') }}</span>
            <span class="pop-val">{{ reportRangeLabel || '—' }}</span>
        </div>
        <div class="pop-row">
            <span class="pop-label">{{ $t('dashboard.onTimeCell.outOfRange.productionWindow') }}</span>
            <span class="pop-val">{{ productionWindowLabel || '—' }}</span>
        </div>
        <div class="pop-row">
            <span class="pop-label">{{ $t('dashboard.onTimeCell.outOfRange.completedAt') }}</span>
            <span class="pop-val">{{ completedAtLabel }}</span>
        </div>
        <div class="pop-divider"></div>
        <div class="pop-note">{{ reasonNote }}</div>
    </div>
</template>
