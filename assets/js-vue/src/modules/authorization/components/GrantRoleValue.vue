<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
    name: "GrantRoleValue",
    props: {
        grant: {
            type: Object,
            required: true
        },
        store: {
            type: Array,
            default: () => ([])
        }
    },

    computed: {
        value() {
            return this.getValueForGrant(this.grant.id)
        }
    },

    methods: {
        getValueForGrant(grantId) {
            return this.store.filter(val => val.grantId === grantId)
        },
        setValue(value, optionSlug = null) {
            const updatedItem = this.value.find(v => v.optionSlug === optionSlug)
            if (updatedItem) {
                this.$emit('valueChanged', [{ ...updatedItem, value }])
            }
        },
        onOptionsChange(newOptions) {
            const updated = this.value.map(item => ({
                ...item,
                value: newOptions.includes(item.optionSlug)
            }))
            this.$emit('valueChanged', updated)
        },
    },

    data() {
        return {
            localOptionsValue: []
        }
    },
})
</script>

<template>
    <div>
        <div v-if="grant.type === 'boolean'">
            <b-form-checkbox
                :unchecked-value="false"
                switch size="lg"
                :checked="value.length && value[0].value"
                @change="setValue($event, null)"
            />
        </div>
        <template v-else>
            <b-form-checkbox-group
                :options="grant.options.map(opt => ({ text: opt.label, value: opt.optionSlug }))"
                buttons
                button-variant="outline-primary"
                v-model="localOptionsValue"
                @change="onOptionsChange"
                :key="grant.id"
            ></b-form-checkbox-group>
        </template>
    </div>
</template>

<style scoped lang="scss">

</style>