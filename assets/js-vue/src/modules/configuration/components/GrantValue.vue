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
        },
        isDisabled: {
            type: Boolean,
            default: false
        },
        disabledDescription: {
            type: String,
            default: null
        }
    },

    computed: {
        options() {
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
                this.localOptionsChecked = this.value
                    .filter(item => item.value === true)
                    .map(item => item.grant_option_slug)
                console.log(this.grant.id, this.localOptionsChecked)
            }
        },

        localOptionsChecked: {
            deep: true,
            immediate: true,
            handler() {
                const newValue = this.options.map(option => {
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
        stacked
        switches
        :disabled="isDisabled"
        :v-b-tooltip.hover="isDisabled" :title="disabledDescription"
    >
        <b-form-checkbox
            v-for="(opt, index) in options" :key="index"
            :value="opt.value"
            :button="false"
            size="lg"
        >
            <span :class="isDisabled && 'opacity-50'">{{ opt.name }}</span>
        </b-form-checkbox>
    </b-form-checkbox-group>
</template>

<style>
</style>