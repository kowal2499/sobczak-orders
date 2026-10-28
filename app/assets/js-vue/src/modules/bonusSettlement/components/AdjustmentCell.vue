<script>
import { defineComponent } from 'vue'
import AdjustmentSummary from './AdjustmentSummary.vue'
import BonusAdjustmentForm from './BonusAdjustmentForm.vue'

/**
 * Wartość obowiązująca wiersza wraz z popoverem korekty.
 *
 * Świadomie NIE jest to `FactorCell` z pulpitu, choć gest i wygląd są te same. Tamta komórka
 * opisuje jedną produkcję: bierze `factorsStack`, ocenia terminowość i ukrywa popover, gdy
 * `production` jest null - a to w pulpicie oznacza pustą komórkę działu. Wiersz rozliczenia nie
 * ma żadnej z tych rzeczy, więc reużycie sprowadzałoby się do podania atrapy produkcji.
 */
// Otwarcie popovera zamyka poprzedni - jeden naraz na całej tabeli.
let openCell = null

export default defineComponent({
    name: 'AdjustmentCell',
    components: { AdjustmentSummary, BonusAdjustmentForm },
    props: {
        entry: {
            type: Object,
            required: true,
        },
        // zamknięty okres jest niezmienny, ale popover nadal się otwiera - notatka przy korekcie
        // to często jedyne wyjaśnienie wartości i musi zostać czytelna po rozliczeniu miesiąca
        readonly: {
            type: Boolean,
            default: false,
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
            return 'bonus-entry-' + this._uid
        },
        isAdjusted() {
            return this.entry.factorsAdjusted !== null
        },
    },
    methods: {
        toggle() {
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
            if (event.target.closest('.bonus-cell-popover') || this.$el.contains(event.target)) {
                return
            }
            this.close()
        },
        onSaved() {
            this.close()
            this.$emit('saved')
        },
    },
})
</script>

<template>
    <span class="d-inline-flex align-items-center">
        <span
            :id="popoverTarget"
            class="cell-value cell-value--interactive"
            :class="{ 'cell-value--adjusted': isAdjusted }"
            role="button"
            @click="toggle"
        >
            <slot />
        </span>

        <font-awesome-icon
            v-if="entry.adjustmentStale"
            v-b-tooltip.hover
            :title="$t('bonus_settlement.adjustment.stale')"
            icon="exclamation-circle"
            class="stale-marker"
        />

        <b-popover
            custom-class="bonus-cell-popover"
            :target="popoverTarget"
            placement="bottom"
            triggers="manual"
            :show="visible"
            @update:show="visible = $event"
        >
            <AdjustmentSummary :entry="entry" />

            <template v-if="!readonly">
                <div class="pop-divider"></div>
                <!-- Formularz czyta wartości z propsów tylko przy tworzeniu, więc po zapisie
                     musi powstać na nowo, inaczej pokazywałby stan sprzed korekty. -->
                <BonusAdjustmentForm
                    :key="`${entry.id}-${entry.factorsAdjusted}-${entry.note}`"
                    :entry="entry"
                    @saved="onSaved"
                />
            </template>
        </b-popover>
    </span>
</template>

<style lang="scss">
// Klasy pop-* są wspólne z formularzem wstawianym do popovera, stąd styl globalny
// przypięty do samego popovera, a nie scoped.
.bonus-cell-popover {
    min-width: 260px;
    max-width: 300px;

    .popover-body {
        padding: 0.75rem 1rem;
        font-size: 0.85rem;
    }

    .pop-subtitle--caps {
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: #6c757d;
        margin-bottom: 0.5rem;
    }
    .pop-row {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
    }
    .pop-val {
        text-align: right;
        white-space: nowrap;
        font-variant-numeric: tabular-nums;
    }
    .pop-divider {
        border-top: 1px dashed #dee2e6;
        margin: 0.75rem 0;
    }
    .pop-error {
        color: #dc3545;
        font-size: 0.8rem;
    }
    .pop-note {
        color: #6c757d;

        &--stale {
            color: #b8860b;
        }
    }
}
</style>

<style scoped lang="scss">
.cell-value {
    display: inline-block;
    padding: 0.1rem 0.5rem;
    border-radius: 0.25rem;
    border-left: 3px solid transparent;
    transition: background-color 0.12s ease-in-out, border-color 0.12s ease-in-out;

    // Wyróżniamy wiersz ruszony ręcznie. Czerwień jest zarezerwowana dla "premia przepadła
    // przez termin" w raporcie on-time, a tutaj termin niczego nie płaci.
    &--adjusted {
        font-weight: 600;
    }

    &--interactive:hover {
        background-color: #e9ecef;
        border-left-color: #adb5bd;
    }
}

.stale-marker {
    color: #b8860b;
    margin-left: 0.35rem;
}
</style>
