<template>
    <ValidationObserver ref="form" class="h-100 d-flex flex-column">
        <div class="p-3 overflow-auto">
            <AgreementLineFactorForm
                v-if="form.agreementLine"
                v-model="form.agreementLine.value"
            />
            <AdjustmentRatioForm
                v-if="form.factorAdjustmentRatio"
                v-model="form.factorAdjustmentRatio"
                :factor="Number(form.agreementLine.value || 0)"
            />
            <AdjustmentBonusForm
                v-if="form.factorAdjustmentBonus"
                v-model="form.factorAdjustmentBonus"
            />
        </div>
        <div class="m-3 mt-auto d-flex justify-content-between">
            <button class="btn btn-secondary" @click="onClose">
                Anuluj
            </button>
            <button class="btn btn-success ml-2" @click.prevent="onSubmit">
                <i class="fa fa-save mr-2" /> Zapisz
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
        }
    },

    components: {
        AgreementLineFactorForm,
        AdjustmentBonusForm,
        AdjustmentRatioForm
    },

    mounted() {
        this.isBusy = true
        return fetchFactors(this.agreementLine.id)
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
            return storeFactors(this.agreementLine.id, { factors })
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

            console.log(agreementLine)
            const factorAdjustmentBonus = (data.filter(item => item.source === 'factor_adjustment_bonus') || [])
                .map(item => ({
                    ...item,
                    value: (Number(item.value) || 0) * 10000 / 100
                }))

            const factorAdjustmentRatio = getUserDepartments()
                .reduce(
                    (prev, current) => {
                        const existing = data.find(item => item.source === 'factor_adjustment_ratio' && item.departmentSlug === current.slug) || null
                        if (existing) {
                            existing.value = Number(existing.value) * 10000 / 100
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

</style>