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

    data() {
        return {
            localOptionsValue: []
        }
    },

    watch: {
        value: {
            handler(newVal) {
                this.localOptionsValue = newVal.filter(v => v.value).map(v => v.optionSlug)
            },
            immediate: true,
            deep: true
        }
    },

    methods: {
        onOptionsChange(newOptions) {
            const updated = this.value.map(item => ({
                ...item,
                value: newOptions.includes(item.optionSlug)
            }))
            this.$emit('input', updated)
        },
        setValue(value, optionSlug = null) {
            const updatedItem = this.value.find(v => v.optionSlug === optionSlug)
            if (updatedItem) {
                this.$emit('input', [{ ...updatedItem, value }])
            }
        }
    },

    computed: {
        optionsValue() {
            return this.value
                .filter(v => v.value)
                .map(v => v.optionSlug)
        }
    },
}
</script>

<template>
    <div class="grant-item">
        <div class="d-flex justify-content-between align-items-center">
            <div class="my-1">
                <h6 class="font-weight-bolder">{{ grant.name }}</h6>
                <div class="text-sm text-muted">{{ grant.description }}</div>
                <div style="font-family: 'Courier New'; font-size: 0.8em" class="mt-2 text-muted">
                    {{ grant.slug }}
                </div>
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
        </div>

    </div>
</template>

<style scoped lang="scss">
.grant-item + .grant-item {
   border-top: 1px solid #e9ecef;
}
</style>