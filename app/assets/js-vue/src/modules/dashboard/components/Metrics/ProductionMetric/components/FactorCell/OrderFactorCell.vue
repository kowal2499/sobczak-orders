<script>
import { defineComponent } from 'vue'
import FactorCell from './FactorCell.vue'
import FactorBreakdown from './FactorBreakdown.vue'
import { fmtDate } from './dates'

/**
 * Komórka szczegółów "Zamówienia w realizacji" / "Zamówienia zrealizowane" - popover pokazuje
 * składowe współczynnika i datę ukończenia pracy działu. Rekordy tych szczegółów nie niosą
 * zaplanowanego okna, więc bez sekcji dat planu i terminowości.
 */
export default defineComponent({
    name: 'OrderFactorCell',
    components: { FactorCell, FactorBreakdown },
    props: {
        factorData: {
            type: Object,
            required: true,
        },
    },
    methods: {
        fmtDate,
    },
})
</script>

<template>
    <FactorCell :factor-data="factorData" trigger="hover" tone="value" v-slot="{ production }">
        <template v-if="production.completedAt">
            <div class="pop-row">
                <span class="pop-label">{{ $t('dashboard.timeliness.actual') }}</span>
                <span class="pop-val">{{ fmtDate(production.completedAt) }}</span>
            </div>
            <div class="pop-divider"></div>
        </template>
        <FactorBreakdown :factor-data="factorData" />
    </FactorCell>
</template>

<style scoped lang="scss">
// "0" i "–" (dział bez produkcji) wyszarzone jak dotąd w tych miernikach - nie konkurują z wartościami
::v-deep .cell-value.cell-value--plain {
    color: #adb5bd;
    font-weight: 400;
}
</style>
