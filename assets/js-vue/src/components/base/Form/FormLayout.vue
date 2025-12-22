<template>
    <ValidationProvider
        :vid="vid || computedVid"
        :name="label || name"
        :rules="rules"
        v-slot="{ errors }"
    >
        <b-form-group
            :label="(!noLabel && (label || name)) || null"
            v-bind="formGroupConfig"
            :label-class="[formGroupConfig.labelClass, errors.length && 'text-danger'].filter(Boolean)"
        >
            <slot :errors="errors" :state="errors.length ? false : null" />
            <b-form-invalid-feedback id="factor-feedback" v-if="errors.length">
                {{ errors[0] }}
            </b-form-invalid-feedback>
        </b-form-group>
    </ValidationProvider>
</template>

<script>
export default {
    name: "FormLayout",
    props: {
        vid: String,
        name: String,
        label: String,
        rules: {
            type: [Object, String],
            default: () => ({})
        },
        formGroupConfig: {
            type: Object,
            default: () => ({})
        },
        noLabel: {
            type: Boolean,
            default: false
        }

    },
    computed: {
        computedVid() {
            if (this.name) { return this.name }
            const base = (this.label || 'field').toString().replace(/\s+/g, '_')
            return `${base}_${this._uid}`
        }
    }
}
</script>

<style scoped>

</style>