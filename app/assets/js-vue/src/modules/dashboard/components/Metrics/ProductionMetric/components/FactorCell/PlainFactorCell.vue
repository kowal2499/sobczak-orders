<script>
import { defineComponent } from 'vue'
import FactorCell from './FactorCell.vue'
import FactorBreakdown from './FactorBreakdown.vue'

/**
 * Komórka raportu "Ukończone zadania produkcyjne" — popover pokazuje wyłącznie składowe
 * współczynnika. Ten raport nie ocenia terminowości i nie pozwala korygować premii.
 */
export default defineComponent({
    name: 'PlainFactorCell',
    components: { FactorCell, FactorBreakdown },
    props: {
        factorData: {
            type: Object,
            required: true,
        },
    }
})
</script>

<template>
    <FactorCell :factor-data="factorData" trigger="hover" tone="value">
        <FactorBreakdown :factor-data="factorData" />
    </FactorCell>
</template>
