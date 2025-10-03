<script>

export default {
    name: 'GrantValue',
    props: {
        grant: {
            type: Object,
            required: true
        },
        value: {
            type: Array,
            default: () => ([])
        },
        userId: {
            type: Number,
            default: null
        },
        roleId: {
            type: Number,
            default: null
        }
    },

    computed: {
        options2() {
            return this.grant.options.length
                ? this.grant.options.map(option => ({
                    name: option.label,
                    value: option.optionSlug,
                }))
                : [{
                    name: this.grant.name,
                    value: null,
                }]
        }
    },

    watch: {
        value: {
            deep: true,
            immediate: true,
            handler() {
                this.localOptionsChecked = this.value.filter(item => item.value).map(item => item.grant_option_slug)
            }
        },

        localOptionsChecked: {
            deep: true,
            immediate: true,
            handler() {
                const newValue = this.options2.map(option => {
                    return {
                        role_id: this.roleId,
                        user_id: this.userId,
                        grant_id: this.grant.id,
                        grant_option_slug: option.value,
                        value: this.localOptionsChecked.includes(option.value),
                    }
                })

                if (JSON.stringify(newValue) === JSON.stringify(this.value)) {
                    return
                }

                this.$emit('input', newValue)
            }
        },
    },

    data() {
        return {
            localOptionsChecked: [],
        }
    },
}
</script>

<template>
    <b-form-checkbox-group
        v-model="localOptionsChecked"
    >
        <b-form-checkbox
            v-for="(opt, index) in options2" :key="index"
            :value="opt.value"
            size="lg"
        >
            {{ grant.type === 'boolean' ? '' : opt.name }}
        </b-form-checkbox>
    </b-form-checkbox-group>
</template>

<style>
</style>