<template>
    <div class="modal fade" :class="{show: show}" :style="getStyle()">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLiveLabel">Wymagane potwierdzenie</h5>
                    <button type="button" class="close" @click.prevent="closeModal()">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>

                <div class="modal-body">
                    <slot></slot>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-success" @click.prevent="$emit('answerYes'); closeModal();">Tak</button>
                    <button type="button" class="btn btn-danger" @click.prevent="closeModal()">Nie</button>
                </div>

            </div>
        </div>
    </div>
</template>

<script>
    export default {
        name: "ConfirmationModal",

        props: [ 'question', 'show' ],

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

</style>