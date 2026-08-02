<script>
import { defineComponent } from 'vue'
import FactorCell from './FactorCell.vue'
import FactorBreakdown from './FactorBreakdown.vue'
import ProductionDates from './ProductionDates.vue'
import WindowAdherence from './WindowAdherence.vue'
import OutOfRangeSummary from './OutOfRangeSummary.vue'

/**
 * Komórka raportu "Ukończone zadania produkcyjne" - popover pokazuje daty produkcji i składowe
 * współczynnika, a dotrzymanie zaplanowanego okna wyłącznie poglądowo: ten raport przypisuje
 * zadanie do miesiąca faktycznego ukończenia i nalicza współczynnik niezależnie od terminu.
 */
export default defineComponent({
    name: 'PlainFactorCell',
    components: { FactorCell, FactorBreakdown, ProductionDates, WindowAdherence, OutOfRangeSummary },
    props: {
        factorData: {
            type: Object,
            required: true,
        },
        // { start: 'YYYY-MM-DD', end: 'YYYY-MM-DD' } - zakres raportu (miesiąc z pulpitu)
        reportRange: {
            type: Object,
            default: () => ({ start: null, end: null })
        },
    }
})
</script>

<template>
    <FactorCell :factor-data="factorData" trigger="hover" tone="value" v-slot="{ state, production }">
        <!-- dział pracował nad linią, ale poza zakresem raportu - sam współczynnik nie przyszedł -->
        <OutOfRangeSummary
            v-if="state === 'outOfRange'"
            :production="production"
            :report-range="reportRange"
        />
        <template v-else>
            <ProductionDates :production="production" />
            <WindowAdherence :production="production" />
            <div class="pop-divider"></div>
            <FactorBreakdown :factor-data="factorData" />
        </template>
    </FactorCell>
</template>
