<template>
    <div class="h-100 d-flex flex-column">
        <div class="p-3 overflow-auto">
            <b-form-group
                :label="$t('orders.factor')"
                label-cols-md="3"
                :description="$t('orders.factorDesc')"
            >
                <b-form-input type="number" :min="0" v-model="form.factor" />
            </b-form-group>

            <AdjustmentRatioForm
                v-model="form.factorAdjustmentRatio"
                :factor="Number(form.factor || 0)"
            />
            <AdjustmentBonusForm v-model="form.factorAdjustmentBonus" />

            <br>
            <br>
            <br>
            <pre>{{ form }}</pre>
        </div>
        <div class="m-3 mt-auto d-flex justify-content-between">
            <button class="btn btn-secondary" @click="$emit('close')">
                Anuluj
            </button>
            <button class="btn btn-success ml-2">
                <i class="fa fa-save mr-2" /> Zapisz
            </button>
        </div>
    </div>
</template>

<script>
import AdjustmentBonusForm from '../components/Factors/AdjustmentBonusForm.vue'
import AdjustmentRatioForm from '../components/Factors/AdjustmentRatioForm.vue'
export default {
    name: 'Factors',
    props: {
        agreementLineId: {
            type: Number
        }
    },
    components: {
        AdjustmentBonusForm,
        AdjustmentRatioForm
    },
    data: () => ({
        form: {
            factor: 0,
            factorAdjustmentRatio: {
                dpt01: {
                    active: false,
                    value: null
                },
                dpt02: {
                    active: false,
                    value: null
                },
                dpt03: {
                    active: false,
                    value: null
                },
                dpt04: {
                    active: false,
                    value: null
                },
                dpt05: {
                    active: false,
                    value: null
                },
            },
            factorAdjustmentBonus: []
        }
    }),
}
</script>



<style scoped lang="scss">

</style>