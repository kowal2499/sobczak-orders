<script>
import { defineComponent } from 'vue'
import { adjustEntry } from '../repository/bonusSettlementRepository'

/**
 * Korekta jednego wiersza rozliczenia. Zapisuje wartość BEZWZGLĘDNĄ, nie różnicę - kierownik
 * wpisuje kwotę, którą pracownik ma dostać, a nie o ile ma ją zmienić.
 *
 * Imiennik z pulpitu (Metrics/ProductionMetric/.../BonusAdjustmentForm.vue) dotyczy zupełnie
 * innego zapisu: dokłada współczynnik ze źródłem do stosu zlecenia. Tamten formularz wymaga
 * komentarza i osobno pyta o premię lub karę - tutaj oba pola byłyby nie na miejscu.
 */
export default defineComponent({
    name: 'BonusAdjustmentForm',
    props: {
        entry: {
            type: Object,
            required: true,
        },
    },
    data() {
        return {
            isSaving: false,
            value: this.entry.factorsAdjusted,
            note: this.entry.note || '',
        }
    },
    computed: {
        parsedValue() {
            return this.value === null || this.value === '' ? null : Number(this.value)
        },
        error() {
            if (this.parsedValue === null) {
                return this.$t('bonus_settlement.adjustment.validation.valueRequired')
            }
            if (Number.isNaN(this.parsedValue) || this.parsedValue < 0) {
                return this.$t('bonus_settlement.adjustment.validation.notNegative')
            }
            return null
        },
        canSubmit() {
            return !this.isSaving && !this.error
        },
        isAdjusted() {
            return this.entry.factorsAdjusted !== null
        },
    },
    methods: {
        submit() {
            if (!this.canSubmit) {
                return
            }
            this.save(this.parsedValue, this.note.trim() || null)
        },
        clear() {
            this.save(null, null)
        },
        save(factorsAdjusted, note) {
            this.isSaving = true
            adjustEntry(this.entry.id, factorsAdjusted, note)
                .then(() => {
                    this.$flash.success(this.$t('bonus_settlement.adjustment.saved'))
                    this.$emit('saved')
                })
                .catch(error => {
                    // popover zostaje otwarty, żeby dało się poprawić wartość
                    this.$flash.danger(
                        error?.response?.data?.error || this.$t('bonus_settlement.adjustment.error')
                    )
                })
                .finally(() => {
                    this.isSaving = false
                })
        },
    },
})
</script>

<template>
    <div>
        <div class="pop-subtitle pop-subtitle--caps">
            {{ $t('bonus_settlement.adjustment.edit') }}
        </div>

        <b-form-input
            v-model="value"
            type="number"
            step="0.01"
            min="0"
            size="sm"
            class="mb-2"
        />
        <b-form-textarea
            v-model="note"
            :placeholder="$t('bonus_settlement.adjustment.notePlaceholder')"
            size="sm"
            rows="2"
            class="mb-2"
        />

        <div v-if="error" class="pop-error mb-2">{{ error }}</div>

        <button
            type="button"
            class="btn btn-primary btn-sm btn-block"
            :disabled="!canSubmit"
            @click="submit"
        >
            {{ $t('bonus_settlement.adjustment.save') }}
        </button>
        <button
            v-if="isAdjusted"
            type="button"
            class="btn btn-outline-secondary btn-sm btn-block"
            :disabled="isSaving"
            @click="clear"
        >
            {{ $t('bonus_settlement.adjustment.clear') }}
        </button>
    </div>
</template>
