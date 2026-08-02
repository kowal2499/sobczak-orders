<script>
import { defineComponent } from 'vue'
import { fmtDate, toDay } from './dates'

/**
 * Sekcja terminowości popovera: zaplanowane okno działu, faktyczne ukończenie i ocena.
 * Używa jej wyłącznie raport premii "w terminie" — pozostałe raporty nie oceniają terminu.
 */
export default defineComponent({
    name: 'TimelinessSummary',
    props: {
        factorData: {
            type: Object,
            required: true,
        },
        production: {
            type: Object,
            required: true,
        },
        // 'bonus' — premia przyznana; 'noBonus' — produkcja poza terminem
        state: {
            type: String,
            required: true,
        },
    },
    computed: {
        hasWindow() {
            return !!(this.production.dateStart && this.production.dateEnd)
        },
        hasCompleted() {
            return !!this.production.completedAt
        },
        // premia należy się dopiero dzięki widełkom — ukończenie wypadło poza zaplanowanym oknem
        withinTolerance() {
            return this.factorData.withinTolerance === true
        },
        // { type: 'onTime'|'delayed'|'early'|'noWindow', days }
        timeliness() {
            if (!this.hasCompleted) {
                return null
            }
            if (!this.hasWindow) {
                return { type: 'noWindow', days: 0 }
            }
            const start = toDay(this.production.dateStart)
            const end = toDay(this.production.dateEnd)
            const done = toDay(this.production.completedAt)

            // liczba dni roboczych pochodzi z backendu — front nie zna kalendarza pracy
            const days = Math.abs(this.factorData.timelinessWorkingDays ?? 0)

            if (done > end) {
                return { type: 'delayed', days }
            }
            if (done < start) {
                return { type: 'early', days }
            }

            return { type: 'onTime', days: 0 }
        },
        statusText() {
            if (!this.timeliness) {
                return ''
            }
            if (this.timeliness.type === 'delayed' || this.timeliness.type === 'early') {
                const days = this.timeliness.days
                const form = days === 1 ? 'One' : 'Many'

                return this.$t(`dashboard.timeliness.${this.timeliness.type}${form}`, { days })
            }

            return this.$t(`dashboard.timeliness.${this.timeliness.type}`)
        },
        statusColorClass() {
            if (this.withinTolerance) {
                return 'text-warning'
            }
            const map = {
                onTime: 'text-success',
                delayed: 'text-danger',
                early: 'text-info',
                noWindow: 'text-muted',
            }

            return this.timeliness ? map[this.timeliness.type] : 'text-muted'
        },
    },
    methods: {
        fmtDate,
    }
})
</script>

<template>
    <div>
        <div v-if="state === 'noBonus'" class="pop-title">{{ $t('dashboard.timeliness.status') }}</div>

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

        <template v-if="state === 'noBonus'">
            <div class="pop-divider"></div>
            <div class="pop-note" :class="statusColorClass">
                <font-awesome-icon icon="clock" />
                {{ statusText }} — {{ $t('dashboard.onTimeCell.noBonusNote') }}
            </div>
        </template>

        <template v-else>
            <div class="pop-row">
                <span class="pop-label">{{ $t('dashboard.timeliness.status') }}</span>
                <span class="pop-val" :class="statusColorClass">
                    <font-awesome-icon :icon="withinTolerance ? 'clock' : 'check-circle'" />
                    {{ statusText }}
                </span>
            </div>

            <div v-if="withinTolerance" class="pop-note pop-note--tolerance">
                {{ $t('dashboard.onTimeCell.withinToleranceNote') }}
            </div>
        </template>
    </div>
</template>
