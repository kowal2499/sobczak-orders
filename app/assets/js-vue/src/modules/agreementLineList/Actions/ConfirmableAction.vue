<template>
    <b-dropdown-item-button @click.prevent="showModal = true">
<!--    <a class="text-decoration-none" :class="anchorClass" href="#"-->
<!--       @click.prevent="showModal = true"-->
<!--    >-->
        <i class="fa" :class="iconClass" /> {{ label }}

        <b-modal
            v-model="showModal"
            size="md"
            centered
            hide-header-close
        >
            <template #modal-title>
                <slot name="title">
                    {{ $t('_confirmation_required') }}
                </slot>
            </template>

            <overlay :show="busy">
                <slot></slot>
            </overlay>

            <template #modal-footer="{ cancel }">
                <b-button variant="outline-secondary" @click="cancel()" :disabled="busy">
                    {{ $t('_cancel') }}
                </b-button>
                <b-button variant="primary" @click="launchAction()" :disabled="busy">
                    <i v-if="busy" class="fa fa-spinner fa-spin mr-1" />
                    {{ $t('_confirm') }}
                </b-button>
            </template>
        </b-modal>
    </b-dropdown-item-button>
</template>

<script>
import Overlay from "../../../components/base/Overlay";
export default {
    name: "ConfirmableAction",

    components: {
        Overlay
    },

    props: {
        anchorClass: {
            type: String,
            default: ''
        },
        iconClass: {
            type: String,
            required: true
        },
        label: {
            type: String,
            required: true
        },
        actionFn: {
            type: Function,
            required: true
        },
        confirmTitle: String,
        confirmMessage: String
    },

    watch: {
        showModal(v) {
            if (v === true) {
                this.busy = false;
            }
        }
    },

    methods: {
        launchAction() {
            this.busy = true;
            return this.actionFn()
                .finally(() => this.showModal = false)
        }
    },

    data: () => ({
        busy: false,
        showModal: false
    })
}
</script>

<style scoped lang="scss">
@use "css/helper/variables" as *;

i.fa {
    color: $primary;
}
</style>