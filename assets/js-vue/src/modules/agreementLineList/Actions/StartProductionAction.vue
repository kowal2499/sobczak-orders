<template>
    <ValidationObserver ref="form" #default="{ invalid }">
        <modal-action :title="$t('agreement_line_list.startProductionForm.modalTitle')" :configuration="{ hideFooter: false, size: 'lg' }">
            <template #open-action="{ open }">
                <a class="dropdown-item p-0"
                   href="#"
                   @click.prevent="open"
                >
                    <i class="fa fa-play" aria-hidden="true"/>
                    {{ $t('startProduction') }}
                </a>
            </template>

            <template #modal-footer="{ close }">
                <div class="d-flex justify-content-end">
                    <button class="btn btn-secondary" @click="close">{{ $t('cancel') }}</button>
                    <button class="btn btn-success ml-2" @click="startProduction(close)">
                        <i class="fa fa-play mr-2" aria-hidden="true" /> {{ $t('agreement_line_list.startProductionForm.startProduction') }}
                    </button>
                </div>
            </template>

            <template #default="{ close }">
                <StartProductionForm
                    v-model="form"
                    :confirmedDate="confirmedDate"
                />
            </template>
        </modal-action>
    </ValidationObserver>
</template>

<script>
import ModalAction from "../../../components/base/ModalAction.vue";
import StartProductionForm from "../Forms/StartProductionForm.vue";
import ApiNewOrder from "../../../api/neworder";
import helpers from "../../../helpers";
import { computeDefaultDatesForDepartment } from "../../../services/productionSchedule";

export default {
    name: "StartProductionAction",

    props: {
        agreementLineId: {
            type: Number,
            required: true
        },
        confirmedDate: {
            type: Date,
            required: true
        }
    },

    components: {
        ModalAction,
        StartProductionForm,
    },

    computed: {
        payload() {
            return (this.form || []).map(row => ({
                department: row.slug,
                dateStart: row.dateStart,
                dateEnd: row.dateEnd,
            }))
        }
    },

    mounted() {
        this.setDefaultValues()
    },

    methods: {
        async startProduction(closeCallback)
        {
            const isValid = await this.$refs.form.validate();

            if (!isValid) {
                // Basic field errors are already displayed by vee-validate
                return
            }

            return ApiNewOrder.startProduction(this.agreementLineId, { schedule: this.payload })
                .then(() => {
                    EventBus.$emit('message', {
                        type: 'success',
                        content: this.$t('addedToSchedule')
                    });
                    EventBus.$emit('statusUpdated');
                    this.$emit('lineChanged');
                    if (closeCallback) {
                        closeCallback()
                    }
                })
        },

        setDefaultValues()
        {
            (this.form || []).forEach(row => {
                const { start, end } = computeDefaultDatesForDepartment(row.slug, new Date(this.confirmedDate.getTime()));
                row.dateStart = start;
                row.dateEnd = end;
            })
        },
    },

    data: () => ({
        form: getForm()
    })
}

function getForm() {
    return helpers.getDepartments().map(d => ({
        slug: d.slug,
        dateStart: null,
        dateEnd: null,
    }));
}

</script>

<style scoped lang="scss">

</style>