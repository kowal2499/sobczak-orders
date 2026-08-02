<script>
import { defineComponent } from 'vue'
import FactorCell from './FactorCell.vue'
import FactorBreakdown from './FactorBreakdown.vue'
import TimelinessSummary from './TimelinessSummary.vue'
import OutOfRangeSummary from './OutOfRangeSummary.vue'
import BonusAdjustmentForm from './BonusAdjustmentForm.vue'

/**
 * Komórka raportu "Ukończone zadania produkcyjne (w terminie)" - pełny wariant popovera:
 * terminowość, składowe współczynnika i formularz korekty premii.
 */
export default defineComponent({
    name: 'OnTimeCell',
    components: { FactorCell, FactorBreakdown, TimelinessSummary, OutOfRangeSummary, BonusAdjustmentForm },
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
        agreementLineId: {
            type: [Number, String],
            default: null,
        },
    }
})
</script>

<template>
    <FactorCell :factor-data="factorData" v-slot="{ state, production, close }">
        <OutOfRangeSummary
            v-if="state === 'outOfRange'"
            :production="production"
            :report-range="reportRange"
        />
        <template v-else>
            <TimelinessSummary
                :factor-data="factorData"
                :production="production"
                :state="state"
            />

            <!-- poza terminem współczynnik jest wyzerowany - nie ma czego rozbijać ani korygować -->
            <template v-if="state === 'bonus'">
                <div class="pop-divider"></div>
                <FactorBreakdown :factor-data="factorData" />
                <div class="pop-divider"></div>
                <BonusAdjustmentForm
                    :agreement-line-id="agreementLineId"
                    :department-slug="production.departmentSlug"
                    @saved="close(); $emit('saved')"
                />
            </template>
        </template>
    </FactorCell>
</template>
