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
            'class': 'mb-0',
            'description': $t('orders.factorDesc')
        }"
        no-label
        #default="{ state }"
    >
        <b-form-input
            type="number"
            :min="0"
            v-model="proxyData"
            :state="state"
            :aria-label="$t('orders.factor')"
            aria-describedby="factor-feedback"
            class="factor-input"
        >
        </b-form-input>
    </FormLayout>
</template>

<style scoped>
.factor-input {
    max-width: 12rem;
}
</style>