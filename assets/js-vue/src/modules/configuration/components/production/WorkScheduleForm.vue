<script>
import proxyValue from "@/mixins/proxyValue";
export default {
    name: "WorkScheduleForm",
    props: {
        value: {
            type: Object,
            default: () => ({})
        },
        isBusy: {
            type: Boolean,
            default: false
        }
    },
    mixins: [proxyValue],
}
</script>

<template>
    <b-overlay :show="isBusy" rounded="sm">
        <ValidationProvider
            :name="$t('config.production.form.dayType')"
            :rules="{ required: true }"
            #default="{ errors }"
        >
            <b-form-group
                :label="$t('config.production.form.dayType')"
                :invalid-feedback="errors.join(' ')"
                :state="errors.length ? false : null"
                :disabled="isBusy"
            >
                <b-form-radio-group v-model="proxyData.dayType">
                    <b-form-radio value="working">{{ $t('config.production.form.working') }}</b-form-radio>
                    <b-form-radio value="holiday">{{ $t('config.production.form.holiday') }}</b-form-radio>
                </b-form-radio-group>
            </b-form-group>
        </ValidationProvider>

        <ValidationProvider
            :name="$t('config.production.form.description')"
            :rules="{ required: false }"
            #default="{ errors }"
        >
            <b-form-group :label="$t('config.production.form.description')" :invalid-feedback="errors.join(' ')" :state="errors.length ? false : null" :disabled="isBusy">
                <b-form-input v-model.trim="proxyData.description" />
            </b-form-group>
        </ValidationProvider>
    </b-overlay>
</template>

<style scoped lang="scss">

</style>