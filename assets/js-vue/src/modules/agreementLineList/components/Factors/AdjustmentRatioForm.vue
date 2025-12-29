<script>
import proxyValue from '@/mixins/proxyValue'
import { DEPARTMENTS } from '@/helpers'

export default {
    name: "AdjustmentRatioForm",
    props: {
        factor: {
            type: Number,
        }
    },
    mixins: [proxyValue],
    methods: {
        computeDepartmentFactor(dptSlug) {
            let factor = this.factor || 0
            if (this.proxyData[dptSlug].active) {
                factor =  Math.round(factor * (this.proxyData[dptSlug].value || 0)) / 100
            }
            return factor
        },
        getDepartment(slug) {
            return DEPARTMENTS.find(dpt => dpt.slug === slug)
        },
        onActiveChange(itemKey, value) {
            if (!value) {
                this.proxyData[itemKey].value = 0
            }
        }
    },
}
</script>

<template>
    <b-form-group
        :label="$t('agreement_line_list.factorsForm.adjustmentRatioSectionTitle')"
        label-cols-md="3"
        label-class="pt-0"
    >
        <b-row v-for="itemKey in Object.keys(proxyData)" :key="itemKey" align-v="start">
            <b-col cols="2">
                <div class="text-nowrap font-weight-bold">{{ getDepartment(itemKey).name }}</div>
                <b-form-checkbox
                    :name="getDepartment(itemKey).name"
                    :unchecked-value="false"
                    switch
                    v-model="proxyData[itemKey].active"
                    @change="onActiveChange(itemKey, $event)"
                />
            </b-col>

            <b-col cols="12" md="5">
                <FormLayout
                    :label="$t('agreement_line_list.factorsForm.adjustmentRatioName')"
                    :rules="proxyData[itemKey].active && 'required|numeric|min_value:0|max_value:100' || null"
                    :form-group-config="{
                        description: $t('agreement_line_list.factorsForm.adjustmentRatioDescription')
                    }"
                    #default="{ state }"
                >
                    <b-form-input type="number"
                        :disabled="!proxyData[itemKey].active"
                        :min="0" :max="100"
                        v-model="proxyData[itemKey].value"
                        :state="state"
                    />
                </FormLayout>
            </b-col>

            <b-col cols="12" md="5" align-h="end" class="ml-auto">
                <b-form-group :label="$t('agreement_line_list.factorsForm.factorPerDpt')" label-class="text-right">
                    <div class="text-primary font-weight-bold text-right" style="font-size: 1.1rem">
                        {{ computeDepartmentFactor(itemKey) }}
                    </div>
<!--                    <b-form-input disabled :value="computeDepartmentFactor(itemKey)" />-->
                </b-form-group>
            </b-col>
        </b-row>
    </b-form-group>
</template>

<style scoped>

</style>