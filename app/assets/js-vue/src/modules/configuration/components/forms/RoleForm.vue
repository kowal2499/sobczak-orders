<script>
import { defineComponent } from 'vue'
import proxyValue from '@/mixins/proxyValue'

export default defineComponent({
    name: 'RoleForm',
    mixins: [proxyValue],

    props: {
        roleId: {
            type: Number // not used yet
        }
    }
})
</script>

<template>
    <ValidationProvider #default="{ errors }" :rules="{ required: true }" :name="$t('name')">
        <b-form-group
            :label="$t('name')"
            :invalid-feedback="errors.join(' ')"
        >
            <b-form-input
                v-model="proxyData.name"
                :state="errors.length === 0 ? null : false"
            />
        </b-form-group>
    </ValidationProvider>
</template>

<style scoped lang="scss">

</style>