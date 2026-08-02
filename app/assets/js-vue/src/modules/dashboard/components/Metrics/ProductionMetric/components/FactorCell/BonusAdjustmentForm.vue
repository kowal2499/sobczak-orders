<script>
import { defineComponent } from 'vue'
import { addCompletedTasksBonus } from '../../../../../repository'

/**
 * Formularz korekty premii w popoverze komórki raportu "Ukończone zadania produkcyjne (w terminie)".
 * Zapisuje współczynnik ze źródłem FACTOR_ADJUSTMENT_BONUS_COMPLETED_TASKS, widoczny wyłącznie
 * w tym raporcie. Po udanym zapisie emituje `saved` — rodzic pobiera wartości od nowa z bazy.
 */
export default defineComponent({
    name: 'BonusAdjustmentForm',
    props: {
        agreementLineId: {
            type: [Number, String],
            default: null,
        },
        departmentSlug: {
            type: String,
            default: null,
        },
    },
    data: () => ({
        isSaving: false,
        form: { type: 'bonus', value: null, comment: '' },
    }),
    computed: {
        typeOptions() {
            return [
                { value: 'bonus', text: this.$t('dashboard.onTimeCell.adjustmentType.bonus') },
                { value: 'penalty', text: this.$t('dashboard.onTimeCell.adjustmentType.penalty') },
            ]
        },
        value() {
            const raw = this.form.value

            return raw === null || raw === '' ? null : Number(raw)
        },
        // komunikat walidacji formularza (null = poprawny)
        error() {
            if (this.value === null || Number.isNaN(this.value) || this.value === 0) {
                return this.$t('dashboard.onTimeCell.validation.valueRequired')
            }
            if (this.form.type === 'bonus' && this.value < 0) {
                return this.$t('dashboard.onTimeCell.validation.bonusMustBePositive')
            }
            if (this.form.type === 'penalty' && this.value > 0) {
                return this.$t('dashboard.onTimeCell.validation.penaltyMustBeNegative')
            }
            if (!this.form.comment.trim()) {
                return this.$t('dashboard.onTimeCell.validation.commentRequired')
            }

            return null
        },
        // komunikat walidacji pokazujemy dopiero gdy użytkownik czegoś dotknął
        touched() {
            return this.value !== null || this.form.comment !== ''
        },
        canSubmit() {
            return !this.isSaving && !this.error && !!this.agreementLineId && !!this.departmentSlug
        },
    },
    methods: {
        submit() {
            if (!this.canSubmit) {
                return
            }
            this.isSaving = true

            addCompletedTasksBonus(this.agreementLineId, {
                departmentSlug: this.departmentSlug,
                // wartość w konwencji ekranu współczynników: wpisane 30 => 0.30
                value: this.value / 100,
                comment: this.form.comment.trim(),
            })
                .then(() => {
                    this.$flash.success(this.$t('dashboard.onTimeCell.adjustmentSaved'))
                    this.form = { type: 'bonus', value: null, comment: '' }
                    this.$emit('saved')
                })
                .catch((error) => {
                    // popover zostaje otwarty, żeby dało się poprawić dane
                    this.$flash.danger(
                        error?.response?.data?.message || this.$t('dashboard.onTimeCell.adjustmentError')
                    )
                })
                .finally(() => {
                    this.isSaving = false
                })
        },
    }
})
</script>

<template>
    <div>
        <div class="pop-subtitle pop-subtitle--caps">{{ $t('dashboard.onTimeCell.addAdjustment') }}</div>

        <div class="d-flex gap-2 mb-2">
            <b-form-select
                v-model="form.type"
                :options="typeOptions"
                size="sm"
                class="mr-2"
            />
            <b-form-input
                v-model="form.value"
                type="number"
                step="0.01"
                size="sm"
                class="pop-adjustment-value"
            />
        </div>
        <b-form-textarea
            v-model="form.comment"
            :placeholder="$t('dashboard.onTimeCell.adjustmentComment')"
            size="sm"
            rows="1"
            class="mb-2"
        />
        <div v-if="error && touched" class="pop-error mb-2">
            {{ error }}
        </div>
        <button
            type="button"
            class="btn btn-primary btn-sm btn-block"
            :disabled="!canSubmit"
            @click="submit"
        >
            {{ $t('dashboard.onTimeCell.addAdjustmentButton') }}
        </button>
    </div>
</template>

<style lang="scss">
.factor-cell-popover {
    .pop-error {
        color: #dc3545;
        font-size: 0.8rem;
    }
    .pop-adjustment-value {
        max-width: 90px;
    }
}
</style>
