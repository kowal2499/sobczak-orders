<template>
    <a class="dropdown-item" :class="anchorClass" href="#"
       @click.prevent="showModal = true"
    >
        <i class="fa mr-3" :class="iconClass" /> {{ label }}

        <b-modal
            v-model="showModal"
            size="md"
            centered
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
                <b-button size="sm" variant="success" @click="launchAction()" :disabled="busy">
                    {{ $t('_yes') }}
                </b-button>
                <b-button size="sm" variant="outline-secondary" @click="cancel()" :disabled="busy">
                    {{ $t('_no') }}
                </b-button>
            </template>
        </b-modal>
    </a>
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

<style scoped>
    i.fa {
        color: #aaa;
    }
</style>