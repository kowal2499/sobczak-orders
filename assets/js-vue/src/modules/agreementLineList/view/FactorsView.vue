<template>
    <ValidationObserver ref="form" class="h-100 d-flex flex-column">
        <div class="p-3 overflow-auto">
            <AgreementLineFactorForm
                v-model="form.factor"
            />
            <AdjustmentRatioForm
                v-model="form.factorAdjustmentRatio"
                :factor="Number(form.factor || 0)"
            />
            <AdjustmentBonusForm v-model="form.factorAdjustmentBonus" />

            <pre>{{ form }}</pre>
        </div>
        <div class="m-3 mt-auto d-flex justify-content-between">
            <button class="btn btn-secondary" @click="$emit('close')">
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

export default {
    name: 'Factors',
    props: {
        agreementLineId: {
            type: Number
        }
    },
    components: {
        AgreementLineFactorForm,
        AdjustmentBonusForm,
        AdjustmentRatioForm
    },
    created() {
        this.form = this.resetForm()
    },
    methods: {
        async onSubmit() {
            if (!this.$refs.form) {
                return
            }
            const isValid = await this.$refs.form.validate()
            console.log('isValid', isValid)
        },

        resetForm() {
            return {
                factor: 0,
                factorAdjustmentRatio: getUserDepartments().reduce(
                    (prev, current) => {
                        prev[current.slug] = {
                            id: null,
                            slug: current.slug,
                            name: current.name,
                            active: false,
                            value: 0
                        }
                        return prev
                    }, {}
                ),
                factorAdjustmentBonus: []
            }
        }
    },

    data: () => ({
        form: {}
    }),
}
</script>



<style scoped lang="scss">

</style>