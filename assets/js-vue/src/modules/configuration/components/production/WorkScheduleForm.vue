<script>
import proxyValue from "@/mixins/proxyValue";
export default {
    name: "WorkScheduleForm",
    props: {
        value: {
            type: Object,
            default: () => ({})
        }
    },
    mixins: [proxyValue],
}
</script>

<template>
    <div>
        <ValidationProvider
            :name="'Typ dnia'"
            #default="{ errors }"
            :rules="{ required: true }"
        >
            <b-form-group :label="'Typ dnia'" :invalid-feedback="errors.join(' ')" :state="errors.length ? false : null">
                <b-form-radio v-model="proxyData.dayType" value="working">Dzień roboczy</b-form-radio>
                <b-form-radio v-model="proxyData.dayType" value="holiday">Dzień wolny</b-form-radio>
            </b-form-group>
        </ValidationProvider>

        <ValidationProvider
            :name="'Opis'"
            #default="{ errors }"
            :rules="{ required: false }"
        >
            <b-form-group :label="'Opis'" :invalid-feedback="errors.join(' ')" :state="errors.length ? false : null">
                <b-form-input v-model.trim="proxyData.description" />
            </b-form-group>
        </ValidationProvider>
    </div>
</template>

<style scoped lang="scss">

</style>