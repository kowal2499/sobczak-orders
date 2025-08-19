<template>
    <div>
        <b-modal v-model="innerValue" v-bind="config" v-on="$listeners">
            <template #modal-header>
                <slot name="modal-header" :close="close">
                    <h5 class="modal-title">{{ title }}</h5>
                    <div class="d-flex" v-if="showClose">
                        <b-button
                            class="text-secondary"
                            @click="close"
                            variant="link"
                        >
                            <i class="fa fa-times" />
                        </b-button>
                    </div>
                </slot>
            </template>
            <template #modal-footer>
                <slot name="modal-footer" :close="close" />
            </template>
            <slot :open="open" :close="close" />
        </b-modal>
        <slot name="open-action" :open="open" />
    </div>
</template>

<script>
export default {
    name: "ModalAction",

    props: {
        title: String,
        value: {
            type: Boolean,
            default: false
        },
        configuration: {
            type: Object,
            default: () => {}
        },
        showClose: {
            type: Boolean,
            default: true
        }
    },

    watch: {
        'value': {
            immediate: true,
            handler() {
                this.innerValue = this.value
            }
        },
        innerValue() {
            this.$emit('input', this.innerValue)
        }
    },

    computed: {
        config() {
            return {
                hideFooter: true,
                centered: true,
                noCloseOnBackdrop: true,
                size: 'md',
                ...this.configuration
            }
        }
    },

    methods: {
        open() {
            this.innerValue = true
        },
        close() {
            this.innerValue = false
        }
    },

    data: () => ({
        innerValue: false
    })
}
</script>


<style scoped lang="scss">

</style>