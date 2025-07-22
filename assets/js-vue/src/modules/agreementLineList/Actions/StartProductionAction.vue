<template>
    <modal-action title="Daty produkcji dla działów" :configuration="{ hideFooter: false }">
        <template #open-action="{ open }">
            <a class="dropdown-item p-0"
               href="#"
               @click.prevent="open"
            >
                <i class="fa fa-play mr-3" aria-hidden="true"/>
                {{ $t('startProduction') }}
            </a>
        </template>

        <template #modal-footer="{ close }">
            <div class="d-flex justify-content-end">
                <button class="btn btn-secondary" @click="close">Anuluj</button>
                <button class="btn btn-success ml-2"><i class="fa fa-play mr-2" aria-hidden="true"/> Rozpocznij produkcję</button>
            </div>
        </template>

        <template #default="{ close }">
            Treść modala
        </template>
    </modal-action>
</template>

<script>
import ModalAction from "../../../components/base/ModalAction.vue";
import ApiNewOrder from "../../../api/neworder";

export default {
    name: "StartProductionAction",

    components: {
        ModalAction
    },

    methods: {
        startProduction() {
            return ApiNewOrder.startProduction(this.line.id)
                .then(({data}) => {
                    this.line.productions = Array.isArray(data) ? data : [];
                    EventBus.$emit('message', {
                        type: 'success',
                        content: this.$t('addedToSchedule')
                    });
                    EventBus.$emit('statusUpdated');
                    this.$emit('lineChanged');
                })
        },
    }
}
</script>

<style scoped lang="scss">

</style>