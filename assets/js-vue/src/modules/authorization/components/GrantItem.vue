<script>

export default {
    name: 'GrantItem',
    props: {
        grant: {
            type: Object,
            required: true
        },
        value: {
            type: Array,
            default: () => ([])
        }
    },

    methods: {
        setOptionsValue(currentOptionsSlugArray) {
            this.value.forEach(item => {
                const currentValue = currentOptionsSlugArray.includes(item.optionSlug)
                if (item.value === currentValue) {
                    return
                }
                this.$emit('input', { ...item, value: currentValue })
            })
        },

        setValue(value, optionSlug = null) {
            const updatedItem = this.value.find(v => v.optionSlug === optionSlug)
            if (updatedItem) {
                updatedItem.value = value
                this.$emit('input', { ...updatedItem, value })
            }
        }
    },

    computed: {
        getOptionsValue() {
            return this.value
                .filter(v => v.value)
                .map(v => v.optionSlug)
        }
    },

    data: () => ({
        test: false
    })
}
</script>

<template>
    <div class="grant-item">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <strong>{{ grant.name }}</strong>
                <div class="text-sm text-muted">{{ grant.description }}</div>
            </div>
            <div>
                <div v-if="grant.type === 'boolean'">
                    <b-form-checkbox
                        :unchecked-value="false"
                        switch size="lg"
                        :checked="value.length && value[0].value"
                        @change="setValue($event, null)"
                    />
                </div>
                <b-form-checkbox-group v-else
                    :options="grant.options.map(opt => ({ text: opt.label, value: opt.optionSlug }))"
                    buttons
                    button-variant="outline-primary"
                    :value="getOptionsValue"
                    @change="setOptionsValue($event)"
                ></b-form-checkbox-group>
            </div>
        </div>

    </div>
</template>

<style scoped lang="scss">
.grant-item + .grant-item {
   border-top: 1px solid #e9ecef;
}
</style>