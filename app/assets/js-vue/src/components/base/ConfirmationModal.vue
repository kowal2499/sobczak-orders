<template>
    <div class="modal fade" :class="{show: show}" :style="getStyle()">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">{{ $t('_confirmation') }}</h5>
                </div>

                <div class="modal-body">
                    <slot></slot>
                </div>

                <div class="modal-footer">
                    <button type="button" :disabled="busy" class="btn btn-outline-secondary" @click.prevent="closeModal()">
                        {{ $t('_cancel') }}
                    </button>
                    <button type="button" :disabled="busy" class="btn btn-primary" @click.prevent="$emit('answerYes')">
                        <i v-if="busy" class="fa fa-spinner fa-spin mr-1"></i>
                        {{ $t('_confirm') }}
                    </button>
                </div>

            </div>
        </div>
    </div>
</template>

<script>
    export default {
        name: "ConfirmationModal",

        props: {
            question: {
                type: String,
                default: ''
            },
            show: {
                type: Boolean,
                default: false
            },
            busy: {
                type: Boolean,
                default: false
            }
        },

        data() {
            return {
                exposed: false
            }
        },

        methods: {
            getStyle() {
                return this.show ? 'display: block;' : 'display: none;'
            },

            closeModal() {
                this.$emit('closeModal', true);
            },
        }
    }
</script>

<style scoped>
    .modal-body {
        text-align: left;
    }
</style>