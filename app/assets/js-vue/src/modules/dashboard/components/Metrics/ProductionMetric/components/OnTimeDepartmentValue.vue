<script>
import { defineComponent } from 'vue'
import { getFactorName, getFactorValue } from '../../../../services/FactorHelper'

/**
 * Prezentacja pojedynczej komórki (dział × zlecenie) w raporcie premii "w terminie".
 * Łączy: wartość współczynnika, etykietę terminowości z ikoną zegara (popover z planowanym
 * oknem vs faktyczną realizacją) oraz popover korekt (bonusy/kary) pod ikoną "i".
 *
 * Każdy popover ma własny, NIEzagnieżdżony target — dzięki temu najechanie na jedną ikonę
 * nie otwiera jednocześnie drugiego popovera.
 */
export default defineComponent({
    name: 'OnTimeDepartmentValue',
    props: {
        factorData: {
            type: Object,
            validator: (val) => Object.hasOwn(val, 'factor') && Object.hasOwn(val, 'factorsStack'),
        },
    },
    computed: {
        infoTarget() {
            return 'ot-info-' + this._uid
        },
        timeTarget() {
            return 'ot-time-' + this._uid
        },
        involved() {
            return this.factorData.factor !== null
        },
        displayValue() {
            return this.involved ? Math.round(this.factorData.factor * 100) / 100 : '–'
        },
        valueClass() {
            if (!this.involved || this.factorData.onTime === false) {
                return 'text-muted'
            }
            return 'font-weight-bold'
        },
        production() {
            return this.factorData.production || null
        },
        hasCompleted() {
            return !!(this.production && this.production.completedAt)
        },
        hasWindow() {
            return !!(this.production && this.production.dateStart && this.production.dateEnd)
        },
        // { type: 'onTime'|'delayed'|'early'|'noWindow', days }
        timeliness() {
            if (!this.hasCompleted) {
                return null
            }
            if (!this.hasWindow) {
                return { type: 'noWindow', days: 0 }
            }
            const start = this.toDay(this.production.dateStart)
            const end = this.toDay(this.production.dateEnd)
            const done = this.toDay(this.production.completedAt)

            if (done > end) {
                return { type: 'delayed', days: this.diffDays(done, end) }
            }
            if (done < start) {
                return { type: 'early', days: this.diffDays(start, done) }
            }
            return { type: 'onTime', days: 0 }
        },
        // krótka etykieta obok ikony zegara
        inlineLabel() {
            if (!this.timeliness) {
                return ''
            }
            return this.$t(`dashboard.timeliness.short.${this.timeliness.type}`)
        },
        // opisowy status w popoverze (z liczbą dni)
        statusText() {
            if (!this.timeliness) {
                return ''
            }
            if (this.timeliness.type === 'delayed' || this.timeliness.type === 'early') {
                return this.$t(`dashboard.timeliness.${this.timeliness.type}`, { days: this.timeliness.days })
            }
            return this.$t(`dashboard.timeliness.${this.timeliness.type}`)
        },
        timelinessColorClass() {
            const map = {
                onTime: 'text-success',
                delayed: 'text-danger',
                early: 'text-info',
                noWindow: 'text-muted',
            }
            return this.timeliness ? map[this.timeliness.type] : 'text-muted'
        },
        hasAdjustments() {
            return this.factorData.factorsStack.length > 0
        },
    },
    methods: {
        getName(source, value) {
            return getFactorName(source, value)
        },
        getValue(source, value) {
            return getFactorValue(source, value)
        },
        toDay(value) {
            // serializowane jako ISO ("2026-05-15T00:00:00+02:00") — bierzemy część dzienną
            return new Date(String(value).slice(0, 10))
        },
        diffDays(a, b) {
            return Math.round((a - b) / 86400000)
        },
        fmtDate(value) {
            return value ? String(value).slice(0, 10) : '—'
        },
    }
})
</script>

<template>
    <div class="d-inline-flex flex-column align-items-center">
        <!-- górna linia: etykieta terminowości + korekty -->
        <div v-if="hasCompleted || hasAdjustments" class="d-inline-flex align-items-center gap-2">
        <!-- terminowość: połączona ikona zegara + etykieta, wspólny target popovera -->
        <span
            v-if="hasCompleted"
            :id="timeTarget"
            class="timeliness-label d-inline-flex align-items-center gap-1"
            :class="timelinessColorClass"
            role="button"
        >
            <font-awesome-icon icon="clock" />
            <span>{{ inlineLabel }}</span>
        </span>
        <b-popover
            v-if="hasCompleted"
            custom-class="factor-data-popover"
            :target="timeTarget"
            placement="bottom"
            triggers="hover"
        >
            <table class="table table-sm mb-0">
                <tbody>
                    <tr>
                        <th class="border-top-0">{{ $t('dashboard.timeliness.planned') }}</th>
                        <td class="border-top-0">
                            <template v-if="hasWindow">
                                {{ fmtDate(production.dateStart) }} – {{ fmtDate(production.dateEnd) }}
                            </template>
                            <template v-else>—</template>
                        </td>
                    </tr>
                    <tr>
                        <th>{{ $t('dashboard.timeliness.actual') }}</th>
                        <td>{{ fmtDate(production.completedAt) }}</td>
                    </tr>
                    <tr>
                        <th>{{ $t('dashboard.timeliness.status') }}</th>
                        <td><span :class="timelinessColorClass">{{ statusText }}</span></td>
                    </tr>
                </tbody>
            </table>
        </b-popover>

        <!-- korekty (bonusy/kary): osobny target -->
        <span v-if="hasAdjustments" :id="infoTarget" class="text-center" role="button">
            <font-awesome-icon icon="info-circle" class="opacity-50" />
        </span>
        <b-popover
            v-if="hasAdjustments"
            custom-class="factor-data-popover"
            :target="infoTarget"
            placement="bottom"
            triggers="hover"
        >
            <table class="table table-sm mb-0">
                <thead>
                    <tr>
                        <th class="border-top-0">Nazwa źródła</th>
                        <th class="border-top-0">Wartość</th>
                        <th class="border-top-0">Opis</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(item, key) in factorData.factorsStack" :key="key">
                        <td>{{ getName(item.source, item.value) }}</td>
                        <td>{{ getValue(item.source, item.value) }}</td>
                        <td>{{ item.description || '-' }}</td>
                    </tr>
                </tbody>
            </table>
        </b-popover>
        </div>

        <!-- wartość liczbowa pod etykietą terminowości, większa czcionka -->
        <div class="cell-value" :class="valueClass">{{ displayValue }}</div>
    </div>
</template>

<style lang="scss">
.factor-data-popover {
    min-width: 350px;
    .popover-body {
        padding: 0;
    }
}
</style>
<style scoped lang="scss">
.timeliness-label {
    font-size: 0.9em;
    white-space: nowrap;
}
.cell-value {
    font-size: 1.35em;
    line-height: 1.2;
}
</style>
