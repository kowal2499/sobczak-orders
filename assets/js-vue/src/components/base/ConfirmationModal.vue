<template>
    <div class="modal fade" :class="{show: show}" :style="getStyle()">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLiveLabel">{{ $t('confirmationIsNeeded') }}</h5>
                    <button type="button" class="close" @click.prevent="closeModal()">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>

                <div class="modal-body">

                    <slot></slot>

                    <div class="text-center" v-if="busy">
                        {{ $t('processing') }} <i class="fa fa-spinner fa-spin"></i>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" :disabled="busy" class="btn btn-success" @click.prevent="$emit('answerYes')">{{ $t('yes') }}</button>
                    <button type="button" :disabled="busy" class="btn btn-danger" @click.prevent="closeModal()">{{ $t('no') }}</button>
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
        font-size: 1.2em;
    }
</style>