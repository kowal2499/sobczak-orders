<template>
    <button href="#" class="d-sm-inline-block btn btn-sm shadow-sm mb-3"
        :class="buttonClass"
        :disabled="isDisabled || isBusy"
        @click="click()"
    >
        <i :class="iconClass" v-if="isBusy === false"></i>
        <i class="fa fa-spinner fa-spin" v-if="isBusy === true"></i>

        <span class="pl-1" v-text="innerText"></span>
    </button>
</template>

<script>
    export default {
        name: "ButtonPlus",

        props: {
            iconClass: {
                type: String,
                default: ''
            },

            buttonClass: {
                type: String,
                default: ''
            },

            innerText: {
                type: String,
                default: ''
            },

            isDisabled: {
                type: Boolean,
                default: false
            },

            isBusy: {
                type: Boolean,
                default: false
            }
        },

        methods: {
            click() {
                this.$emit('clicked');
            }
        }
    }
</script>

<style scoped>
</style>