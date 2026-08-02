<script>
import { defineComponent } from 'vue'
import FactorCell from './FactorCell.vue'
import FactorBreakdown from './FactorBreakdown.vue'
import ProductionDates from './ProductionDates.vue'
import WindowAdherence from './WindowAdherence.vue'

/**
 * Komórka raportu "Ukończone zadania produkcyjne" — popover pokazuje daty produkcji i składowe
 * współczynnika, a dotrzymanie zaplanowanego okna wyłącznie poglądowo: ten raport przypisuje
 * zadanie do miesiąca faktycznego ukończenia i nalicza współczynnik niezależnie od terminu.
 */
export default defineComponent({
    name: 'PlainFactorCell',
    components: { FactorCell, FactorBreakdown, ProductionDates, WindowAdherence },
    props: {
        factorData: {
            type: Object,
            required: true,
        },
    }
})
</script>

<template>
    <FactorCell :factor-data="factorData" trigger="hover" tone="value" v-slot="{ production }">
        <ProductionDates :production="production" />
        <WindowAdherence :production="production" />
        <div class="pop-divider"></div>
        <FactorBreakdown :factor-data="factorData" />
    </FactorCell>
</template>
