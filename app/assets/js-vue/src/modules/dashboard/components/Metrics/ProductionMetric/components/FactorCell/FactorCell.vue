<script>
import { defineComponent } from 'vue'

/**
 * Wartość współczynnika w komórce tabeli (dział × zlecenie) wraz z popoverem szczegółów.
 *
 * Komponent odpowiada wyłącznie za prezentację wartości, stan otwarcia popovera i wyliczenie
 * stanu pozycji z danych backendu. Treść popovera składa rodzic przez slot — dzięki temu raport
 * premii "w terminie" i raport ukończonych zadań używają tej samej komórki, wstawiając do niej
 * różne sekcje (patrz OnTimeCell.vue i PlainFactorCell.vue).
 *
 * Wartość w tabeli:
 *   > 0  premia się należy          (hover: zielone tło)
 *   0    premia nie należy się      (produkcja poza terminem, hover: czerwone tło)
 *   –    brak rozliczanej produkcji (poza zakresem raportu, hover: szare tło)
 */
// Aktualnie otwarta komórka — otwarcie popovera zamyka poprzedni (jeden popover naraz).
let openCell = null

export default defineComponent({
    name: 'FactorCell',
    props: {
        factorData: {
            type: Object,
            validator: (val) => Object.hasOwn(val, 'factor') && Object.hasOwn(val, 'factorsStack'),
        },
        // 'click' — popover z formularzem korekty; 'hover' — sam podgląd
        trigger: {
            type: String,
            default: 'click',
            validator: (val) => ['click', 'hover'].includes(val),
        },
        // 'timeliness' — kolor wg przyznania premii; 'value' — kolor wg samej wartości
        // (raport bez terminowości nie ma czego oceniać na czerwono)
        tone: {
            type: String,
            default: 'timeliness',
            validator: (val) => ['timeliness', 'value'].includes(val),
        },
    },
    data: () => ({
        visible: false,
    }),
    beforeDestroy() {
        this.close()
    },
    computed: {
        popoverTarget() {
            return 'factor-cell-' + this._uid
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
            if (this.tone === 'value') {
                return (this.factorData.factor || 0) > 0 ? 'cell-value--bonus' : 'cell-value--plain'
            }
            return `cell-value--${this.state}`
        },
        hasPopover() {
            // brak jakichkolwiek danych o produkcji => nie ma czego pokazać
            return this.production !== null
        },
        isHoverTrigger() {
            return this.trigger === 'hover'
        },
        // hover obsługuje sam b-popover; klik sterujemy ręcznie, bo tylko jeden popover ma być otwarty
        popoverBind() {
            return this.isHoverTrigger
                ? { triggers: 'hover' }
                : { triggers: 'manual', show: this.visible }
        },
    },
    methods: {
        toggle() {
            if (!this.hasPopover || this.isHoverTrigger) {
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
            if (event.target.closest('.factor-cell-popover') || this.$el.contains(event.target)) {
                return
            }
            this.close()
        },
    }
})
</script>

<template>
    <div class="d-inline-block">
        <span
            :id="popoverTarget"
            class="cell-value"
            :class="[stateClass, { 'cell-value--interactive': hasPopover }]"
            :role="hasPopover && !isHoverTrigger ? 'button' : null"
            @click="toggle"
        >{{ displayValue }}</span>

        <b-popover
            v-if="hasPopover"
            custom-class="factor-cell-popover"
            :target="popoverTarget"
            placement="bottom"
            v-bind="popoverBind"
            @update:show="visible = $event"
        >
            <slot
                :state="state"
                :production="production"
                :display-value="displayValue"
                :close="close"
            />
        </b-popover>
    </div>
</template>

<style lang="scss">
// Klasy pop-* są wspólne dla sekcji wstawianych do popovera przez rodzica — stąd styl globalny
// przypięty do samego popovera, a nie scoped w komponentach sekcji.
.factor-cell-popover {
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
    .pop-note--tolerance {
        margin-top: 0.35rem;
        color: #b8860b;
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
    &--bonus,
    &--plain {
        font-weight: 600;
    }

    &--interactive:hover {
        &.cell-value--bonus {
            background-color: #d7f0e0;
            border-left-color: #28a745;
        }
        &.cell-value--noBonus {
            background-color: #f8d7da;
            border-left-color: #dc3545;
        }
        &.cell-value--outOfRange,
        &.cell-value--plain {
            background-color: #e9ecef;
            border-left-color: #adb5bd;
        }
    }
}
</style>
