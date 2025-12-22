<script>
import proxyValue from '@/mixins/proxyValue'

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
            const factor = this.factor || 0
            if (!this.proxyData[dptSlug].active) {
                return factor
            }
            return factor * (this.proxyData[dptSlug].value || 0) / 100
        }
    }
}
</script>

<template>
    <div>
        <b-form-group
            :label="$t('agreement_line_list.factorsForm.adjustmentRatioSectionTitle')"
            label-cols-md="3"
            label-class="pt-0"
        >
            <b-row v-for="itemKey in Object.keys(proxyData)" :key="itemKey" align-v="start">
                <b-col cols="2">
                    <div class="text-nowrap font-weight-bold">{{ proxyData[itemKey].name }}</div>
                    <b-form-checkbox
                        :name="proxyData[itemKey].name"
                        :unchecked-value="false"
                        switch
                        v-model="proxyData[itemKey].active"
                    />
                </b-col>

                <b-col cols="12" md="5">
                    <FormLayout
                        v-if="proxyData[itemKey].active"
                        :label="$t('agreement_line_list.factorsForm.adjustmentRatioName')"
                        :rules="'required|numeric|min_value:0|max_value:100'"
                        :form-group-config="{
                            description: $t('agreement_line_list.factorsForm.adjustmentRatioDescription')
                        }"
                        #default="{ state }"
                    >
                        <b-form-input type="number"
                            :min="0" :max="100"
                            v-model="proxyData[itemKey].value"
                            :state="state"
                        />
                    </FormLayout>
                </b-col>

                <b-col cols="12" md="5" align-h="end" class="ml-auto">
                    <b-form-group :label="$t('agreement_line_list.factorsForm.factorPerDpt')">
                        <b-form-input disabled :value="computeDepartmentFactor(itemKey)" />
                    </b-form-group>
                </b-col>
            </b-row>
        </b-form-group>
    </div>
</template>

<style scoped>

</style>