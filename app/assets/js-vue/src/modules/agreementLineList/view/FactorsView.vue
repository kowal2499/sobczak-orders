<template>
    <ValidationObserver ref="form" class="h-100 d-flex flex-column">
        <div class="factors-view__body overflow-auto">
            <SectionBlock v-if="form.agreementLine" class="factors-view__section">
                <SectionBlockTitle :title="$t('orders.factor')" class="mb-3" />
                <AgreementLineFactorForm v-model="form.agreementLine.value" />
            </SectionBlock>

            <SectionBlock v-if="form.factorAdjustmentRatio" class="factors-view__section">
                <SectionBlockTitle
                    :title="$t('agreement_line_list.factorsForm.adjustmentRatioSectionTitle')"
                    class="mb-3"
                />
                <AdjustmentRatioForm
                    v-model="form.factorAdjustmentRatio"
                    :factor="Number(form.agreementLine.value || 0)"
                />
            </SectionBlock>

            <SectionBlock v-if="form.factorAdjustmentBonus" class="factors-view__section">
                <SectionBlockTitle :title="$t('agreement_line_list.factorsForm.adjustmentBonusSectionTitle')">
                    <template #actions>
                        <button type="button" class="btn btn-outline-primary btn-sm" @click="$refs.bonusForm.addRow()">
                            {{ $t('_add') }}
                        </button>
                    </template>
                </SectionBlockTitle>
                <AdjustmentBonusForm ref="bonusForm" v-model="form.factorAdjustmentBonus" />
            </SectionBlock>
        </div>
        <div class="form-actions mt-auto factors-view__footer">
            <button type="button" class="btn btn-outline-secondary" @click="onClose">
                Anuluj
            </button>
            <button type="button" class="btn btn-primary" :disabled="isBusy" @click.prevent="onSubmit">
                <font-awesome-icon v-if="isBusy" icon="spinner" spin />Zapisz
            </button>
        </div>
    </ValidationObserver>
</template>

<script>
import AdjustmentBonusForm from '../components/Factors/AdjustmentBonusForm.vue'
import AdjustmentRatioForm from '../components/Factors/AdjustmentRatioForm.vue'
import AgreementLineFactorForm from '../components/Factors/AgreementLineFactorForm.vue'
import { getUserDepartments } from '@/helpers'
import { fetchFactors, storeFactors } from '../repository/agreementLineRepository'

const emptyFactor = () => ({
    id: null,
    departmentSlug: null,
    description: null,
    value: 0
})
export default {
    name: 'FactorsView',
    props: {
        agreementLine: {
            type: Object
        },
        agreementLineId: {
            type: [Number, String],
            required: true
        }
    },

    components: {
        AgreementLineFactorForm,
        AdjustmentBonusForm,
        AdjustmentRatioForm
    },

    mounted() {
        this.isBusy = true
        return fetchFactors(this.agreementLineId)
            .then(({data}) => this.form = this.resetForm(data))
            .finally(() => this.isBusy = false)
    },

    methods: {
        async onSubmit() {
            if (!this.$refs.form) {
                return
            }
            const isValid = await this.$refs.form.validate()
            if (!isValid) {
                return
            }
            const factors = [
                this.form.agreementLine,
                ...Object.values(this.form.factorAdjustmentRatio).filter(item => item.active),
                ...this.form.factorAdjustmentBonus,
            ].map((item) => ({
                ...item,
                value: Number(item.value || 0) / 100
            }))

            this.isBusy = true
            return storeFactors(this.agreementLineId, { factors })
                .then(() => {
                    EventBus.$emit('message', {
                        type: 'success',
                        content: this.$t('_saveSuccess')
                    })
                    this.onClose()
                })
                .catch(() => EventBus.$emit('message', {
                    type: 'error',
                    content: this.$t('_saveError')
                }))
                .finally(() => {
                    this.isBusy = false
                })
        },

        resetForm(data = []) {
            let agreementLine = data.find(item => item.source === 'agreement_line') || {
                ...emptyFactor(),
                source: 'agreement_line',
                value: Number(this.agreementLine.factor || 0)
            }
            agreementLine.value = Number(agreementLine.value) * 10000 / 100

            const factorAdjustmentBonus = (data.filter(item => item.source === 'factor_adjustment_bonus') || [])
                .map(item => ({
                    ...item,
                    value: Math.round(Number(item.value || 0) * 10000) / 100
                }))

            const factorAdjustmentRatio = getUserDepartments()
                .reduce(
                    (prev, current) => {
                        const existing = data.find(item => item.source === 'factor_adjustment_ratio' && item.departmentSlug === current.slug) || null
                        if (existing) {
                            existing.value = Math.round(existing.value * 10000) / 100
                            existing.active = true
                        }

                        prev[current.slug] = {
                            ...emptyFactor(),
                            source: 'factor_adjustment_ratio',
                            departmentSlug: current.slug,
                            active: false,
                            ...(existing || {}),
                        }
                        return prev
                    }, {}
                );
            return {
                agreementLine,
                factorAdjustmentRatio,
                factorAdjustmentBonus
            }
        },

        onClose() {
            this.$emit('close')
        }
    },

    data: () => ({
        form: {},
        isBusy: false
    }),
}
</script>

<style scoped lang="scss">
.factors-view__body {
    padding: 0 1.25rem;
}

.factors-view__section + .factors-view__section {
    margin-top: 1rem;
}

.factors-view__footer {
    padding: 1rem 1.25rem 1.25rem;
}
</style>