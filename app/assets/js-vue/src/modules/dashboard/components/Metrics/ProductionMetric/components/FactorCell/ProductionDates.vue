<script>
import { defineComponent } from 'vue'
import { fmtDate } from './dates'

/**
 * Zaplanowane okno pracy działu i faktyczna data ukończenia - same daty, bez oceny terminowości.
 * Ocenę dokłada TimelinessSummary tam, gdzie termin wpływa na premię.
 */
export default defineComponent({
    name: 'ProductionDates',
    props: {
        production: {
            type: Object,
            required: true,
        },
    },
    computed: {
        hasWindow() {
            return !!(this.production.dateStart && this.production.dateEnd)
        },
    },
    methods: {
        fmtDate,
    }
})
</script>

<template>
    <div>
        <div class="pop-row">
            <span class="pop-label">{{ $t('dashboard.timeliness.planned') }}</span>
            <span class="pop-val">
                <template v-if="hasWindow">
                    {{ fmtDate(production.dateStart) }} – {{ fmtDate(production.dateEnd) }}
                </template>
                <template v-else>—</template>
            </span>
        </div>
        <div class="pop-row">
            <span class="pop-label">{{ $t('dashboard.timeliness.actual') }}</span>
            <span class="pop-val">{{ fmtDate(production.completedAt) }}</span>
        </div>
    </div>
</template>
