<script>
import { defineComponent } from 'vue'
import { MONTHS } from '@/services/datesService'
import { getFactorName, getFactorValue } from '../../../../services/FactorHelper'

/**
 * Prezentacja pojedynczej komórki (dział × zlecenie) w raporcie premii "w terminie".
 *
 * W tabeli widoczna jest WYŁĄCZNIE finalna wartość współczynnika:
 *   > 0  premia się należy          (hover: zielone tło)
 *   0    premia nie należy się      (produkcja poza terminem, hover: czerwone tło)
 *   –    brak rozliczanej produkcji (poza zakresem raportu, hover: szare tło)
 *
 * Klik otwiera jeden popover z pełnym podsumowaniem pozycji — treść zależy od stanu.
 * Popover jest wyzwalany klikiem (nie hoverem), bo w wariancie premii zawiera formularz.
 */
// Aktualnie otwarta komórka — otwarcie popovera zamyka poprzedni (jeden popover naraz).
let openCell = null

const BAR_COLORS = {
    agreement_line: '#a0a4a8',
    factor_adjustment_ratio: '#f472a0',
    bonus: '#2ba7c4',
    penalty: '#f472a0',
}

export default defineComponent({
    name: 'OnTimeDepartmentValue',
    props: {
        factorData: {
            type: Object,
            validator: (val) => Object.hasOwn(val, 'factor') && Object.hasOwn(val, 'factorsStack'),
        },
        // { start: 'YYYY-MM-DD', end: 'YYYY-MM-DD' } — zakres raportu (miesiąc z pulpitu)
        reportRange: {
            type: Object,
            default: () => ({ start: null, end: null })
        },
    },
    data: () => ({
        visible: false,
        // formularz korekty — na razie tylko UI, bez wysyłki do API (etap 2)
        adjustment: { type: 'bonus', value: 0, comment: '' },
    }),
    beforeDestroy() {
        this.close()
    },
    computed: {
        popoverTarget() {
            return 'ot-cell-' + this._uid
        },
        production() {
            return this.factorData.production || null
        },
        inRange() {
            return this.factorData.inRange !== false && this.factorData.factor !== null
        },
        onTime() {
            return this.factorData.onTime !== false
        },
        // 'bonus' | 'noBonus' | 'outOfRange'
        state() {
            if (!this.inRange) {
                return 'outOfRange'
            }
            return this.onTime ? 'bonus' : 'noBonus'
        },
        displayValue() {
            if (this.state === 'outOfRange') {
                return '–'
            }
            return Math.round((this.factorData.factor || 0) * 100) / 100
        },
        stateClass() {
            return `cell-value--${this.state}`
        },
        hasPopover() {
            // brak jakichkolwiek danych o produkcji => nie ma czego pokazać
            return this.production !== null
        },
        hasWindow() {
            return !!(this.production && this.production.dateStart && this.production.dateEnd)
        },
        hasCompleted() {
            return !!(this.production && this.production.completedAt)
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
        // składowe współczynnika + wiersz finalny, z szerokościami pasków
        breakdown() {
            const stack = this.factorData.factorsStack || []
            const final = this.factorData.factor || 0
            const scale = Math.max(...stack.map(i => Math.abs(i.value)), Math.abs(final), 0.0001)

            return stack.map(item => ({
                key: `${item.source}-${item.value}-${item.description || ''}`,
                label: this.rowLabel(item),
                value: getFactorValue(item.source, item.value),
                negative: item.value < 0,
                width: (Math.abs(item.value) / scale) * 100,
                color: this.barColor(item),
            }))
        },
        finalBarWidth() {
            const stack = this.factorData.factorsStack || []
            const final = this.factorData.factor || 0
            const scale = Math.max(...stack.map(i => Math.abs(i.value)), Math.abs(final), 0.0001)
            return (Math.abs(final) / scale) * 100
        },
        adjustmentTypeOptions() {
            return [
                { value: 'bonus', text: this.$t('dashboard.onTimeCell.adjustmentType.bonus') },
                { value: 'penalty', text: this.$t('dashboard.onTimeCell.adjustmentType.penalty') },
            ]
        },
        reportRangeLabel() {
            return this.rangeLabel(this.reportRange.start, this.reportRange.end)
        },
        productionWindowLabel() {
            return this.hasWindow
                ? this.rangeLabel(this.production.dateStart, this.production.dateEnd)
                : null
        },
    },
    methods: {
        toggle() {
            if (!this.hasPopover) {
                return
            }
            if (this.visible) {
                this.close()
                return
            }
            if (openCell && openCell !== this) {
                openCell.close()
            }
            openCell = this
            this.visible = true
            document.addEventListener('mousedown', this.onDocumentMouseDown)
        },
        close() {
            this.visible = false
            document.removeEventListener('mousedown', this.onDocumentMouseDown)
            if (openCell === this) {
                openCell = null
            }
        },
        onDocumentMouseDown(event) {
            // klik poza popoverem i poza samą wartością zamyka popover
            if (event.target.closest('.ontime-cell-popover') || this.$el.contains(event.target)) {
                return
            }
            this.close()
        },
        rowLabel(item) {
            const name = getFactorName(item.source, item.value)
            return item.description ? `${name} · ${item.description}` : name
        },
        barColor(item) {
            if (item.source === 'factor_adjustment_bonus') {
                return item.value < 0 ? BAR_COLORS.penalty : BAR_COLORS.bonus
            }
            return BAR_COLORS[item.source] || BAR_COLORS.agreement_line
        },
        toDay(value) {
            // serializowane jako ISO ("2026-05-15T00:00:00+02:00") — bierzemy część dzienną
            return new Date(String(value).slice(0, 10))
        },
        diffDays(a, b) {
            return Math.round((a - b) / 86400000)
        },
        fmtDate(value) {
            if (!value) {
                return '—'
            }
            const [y, m, d] = String(value).slice(0, 10).split('-')
            return `${d}.${m}.${y}`
        },
        fmtDayMonth(value) {
            if (!value) {
                return '—'
            }
            const [, m, d] = String(value).slice(0, 10).split('-')
            return `${d}.${m}`
        },
        // "lipiec 2026 (01.07 – 31.07)"
        rangeLabel(start, end) {
            if (!start || !end) {
                return null
            }
            const [year, month] = String(start).slice(0, 10).split('-')
            const monthName = this.$t(MONTHS[Number(month) - 1].name).toLowerCase()
            return `${monthName} ${year} (${this.fmtDayMonth(start)} – ${this.fmtDayMonth(end)})`
        },
    }
})
</script>

<template>
    <div class="d-inline-block">
        <span
            :id="popoverTarget"
            class="cell-value"
            :class="[stateClass, { 'cell-value--clickable': hasPopover }]"
            role="button"
            @click="toggle"
        >{{ displayValue }}</span>

        <b-popover
            v-if="hasPopover"
            custom-class="ontime-cell-popover"
            :target="popoverTarget"
            placement="bottom"
            triggers="manual"
            :show.sync="visible"
        >
            <!-- 1. poza zakresem raportu -->
            <div v-if="state === 'outOfRange'" class="pop-section">
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

            <!-- 2. produkcja poza terminem — brak premii -->
            <div v-else-if="state === 'noBonus'" class="pop-section">
                <div class="pop-title">{{ $t('dashboard.timeliness.status') }}</div>
                <div class="pop-row">
                    <span class="pop-label">{{ $t('dashboard.timeliness.planned') }}</span>
                    <span class="pop-val">
                        <template v-if="hasWindow">
                            {{ fmtDayMonth(production.dateStart) }} – {{ fmtDayMonth(production.dateEnd) }}
                        </template>
                        <template v-else>—</template>
                    </span>
                </div>
                <div class="pop-row">
                    <span class="pop-label">{{ $t('dashboard.timeliness.actual') }}</span>
                    <span class="pop-val">{{ fmtDate(production.completedAt) }}</span>
                </div>
                <div class="pop-divider"></div>
                <div class="pop-note" :class="timelinessColorClass">
                    <font-awesome-icon icon="clock" />
                    {{ statusText }} — {{ $t('dashboard.onTimeCell.noBonusNote') }}
                </div>
            </div>

            <!-- 3. premia się należy — pełne podsumowanie + korekta -->
            <div v-else class="pop-section">
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
                <div class="pop-row">
                    <span class="pop-label">{{ $t('dashboard.timeliness.status') }}</span>
                    <span class="pop-val" :class="timelinessColorClass">
                        <font-awesome-icon icon="check-circle" />
                        {{ statusText }}
                    </span>
                </div>

                <div class="pop-divider"></div>
                <div class="pop-subtitle">{{ $t('dashboard.onTimeCell.factorBreakdown') }}</div>

                <div v-for="row in breakdown" :key="row.key" class="pop-bar-row">
                    <div class="d-flex justify-content-between">
                        <span>{{ row.label }}</span>
                        <span :class="row.negative ? 'text-danger' : 'text-info'">{{ row.value }}</span>
                    </div>
                    <div class="pop-bar">
                        <div :style="{ width: row.width + '%', backgroundColor: row.color }"></div>
                    </div>
                </div>

                <div class="pop-bar-row pop-bar-row--final">
                    <div class="d-flex justify-content-between font-weight-bold">
                        <span>{{ $t('dashboard.onTimeCell.finalValue') }}</span>
                        <span>{{ displayValue }}</span>
                    </div>
                    <div class="pop-bar">
                        <div :style="{ width: finalBarWidth + '%', backgroundColor: '#212529' }"></div>
                    </div>
                </div>

                <div class="pop-divider"></div>
                <div class="pop-subtitle pop-subtitle--caps">{{ $t('dashboard.onTimeCell.addAdjustment') }}</div>

                <!-- TODO etap 2: wysyłka korekty (POST /production/factor/{id}, FactorSource::FACTOR_ADJUSTMENT_BONUS) -->
                <div class="d-flex gap-2 mb-2">
                    <b-form-select
                        v-model="adjustment.type"
                        :options="adjustmentTypeOptions"
                        size="sm"
                        class="mr-2"
                    />
                    <b-form-input
                        v-model="adjustment.value"
                        type="number"
                        step="0.01"
                        size="sm"
                        class="pop-adjustment-value"
                    />
                </div>
                <b-form-textarea
                    v-model="adjustment.comment"
                    :placeholder="$t('dashboard.onTimeCell.adjustmentComment')"
                    size="sm"
                    rows="1"
                    class="mb-2"
                />
                <button type="button" class="btn btn-primary btn-sm btn-block" disabled>
                    {{ $t('dashboard.onTimeCell.addAdjustmentButton') }}
                </button>
            </div>
        </b-popover>
    </div>
</template>

<style lang="scss">
.ontime-cell-popover {
    min-width: 320px;
    max-width: 340px;

    .popover-body {
        padding: 0.75rem 1rem;
        font-size: 0.85rem;
    }

    .pop-title {
        font-weight: 600;
        margin-bottom: 0.6rem;
    }
    .pop-subtitle {
        font-weight: 600;
        margin-bottom: 0.5rem;

        &--caps {
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: #6c757d;
        }
    }
    .pop-row {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 0.35rem;

        .pop-label {
            color: inherit;
        }
        .pop-val {
            text-align: right;
            white-space: nowrap;
        }
    }
    .pop-divider {
        border-top: 1px dashed #dee2e6;
        margin: 0.75rem 0;
    }
    .pop-note {
        color: #6c757d;
    }
    .pop-bar-row {
        margin-bottom: 0.6rem;

        &--final {
            margin-top: 0.75rem;
        }
    }
    .pop-bar {
        height: 6px;
        margin-top: 0.2rem;
        background: #f1f3f5;

        div {
            height: 100%;
        }
    }
    .pop-adjustment-value {
        max-width: 90px;
    }
}
</style>

<style scoped lang="scss">
.cell-value {
    display: inline-block;
    padding: 0.15rem 0.6rem;
    border-radius: 0.25rem;
    font-size: 1.35em;
    line-height: 1.3;
    border-left: 3px solid transparent;
    transition: background-color 0.12s ease-in-out, border-color 0.12s ease-in-out;

    &--outOfRange {
        color: #adb5bd;
    }
    &--noBonus {
        color: #adb5bd;
    }
    &--bonus {
        font-weight: 600;
    }

    &--clickable:hover {
        &.cell-value--bonus {
            background-color: #d7f0e0;
            border-left-color: #28a745;
        }
        &.cell-value--noBonus {
            background-color: #f8d7da;
            border-left-color: #dc3545;
        }
        &.cell-value--outOfRange {
            background-color: #e9ecef;
            border-left-color: #adb5bd;
        }
    }
}
</style>
