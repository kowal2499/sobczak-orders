<script>
import proxyValue from '@/mixins/proxyValue'

export default {
    name: "AgreementLineFactorForm",
    mixins: [proxyValue],
}
</script>

<template>
    <FormLayout
        :name="$t('orders.factor')"
        rules="required|numeric|min_value:0"
        :form-group-config="{
            'label-cols-md': 3,
            'label-class': 'pt-0',
            'description': $t('orders.factorDesc')
        }"
        #default="{ state }"
    >
        <b-form-input
            type="number"
            :min="0"
            v-model="proxyData"
            :state="state"
            aria-describedby="factor-feedback"
        >
        </b-form-input>
    </FormLayout>
</template>

<style scoped>

</style>