<script>
import { defineComponent } from 'vue'
import { MONTHS } from '@/services/datesService'
import { fmtDayMonth } from './dates'

/**
 * Sekcja popovera dla pozycji, której okno produkcji nie pokrywa się z zakresem raportu.
 * Pojęcie "poza zakresem" istnieje tylko w raporcie premii "w terminie".
 */
export default defineComponent({
    name: 'OutOfRangeSummary',
    props: {
        production: {
            type: Object,
            required: true,
        },
        // { start: 'YYYY-MM-DD', end: 'YYYY-MM-DD' } — zakres raportu (miesiąc z pulpitu)
        reportRange: {
            type: Object,
            default: () => ({ start: null, end: null })
        },
    },
    computed: {
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
        <div class="pop-divider"></div>
        <div class="pop-note">{{ $t('dashboard.onTimeCell.outOfRange.note') }}</div>
    </div>
</template>
