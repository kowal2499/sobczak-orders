<template>
    <ValidationObserver ref="form" #default="{ invalid }">
        <modal-action :title="$t('agreement_line_list.startProductionForm.modalTitle')" :configuration="{ hideFooter: false, size: 'lg' }">
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
                    <button class="btn btn-secondary" @click="close">{{ $t('cancel') }}</button>
                    <button class="btn btn-success ml-2" @click="startProduction(close)">
                        <i class="fa fa-play mr-2" aria-hidden="true" /> {{ $t('agreement_line_list.startProductionForm.startProduction') }}
                    </button>
                </div>
            </template>

            <template #default="{ close }">
                <StartProductionForm v-model="form" />
            </template>
        </modal-action>
    </ValidationObserver>
</template>

<script>
import ModalAction from "../../../components/base/ModalAction.vue";
import StartProductionForm from "../Forms/StartProductionForm.vue";
import ApiNewOrder from "../../../api/neworder";
import helpers, { getLocalDate, DPT_GLUEING } from "../../../helpers";

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
        productionDepartments() {
            return helpers.getDepartments()
        },

        payload() {
            return Object.keys(this.form).map(key => ({
                department: key,
                [this.form[key][0].id]: this.form[key][0].value,
                [this.form[key][1].id]: this.form[key][1].value,
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
                EventBus.$emit('message', {
                    type: 'error',
                    content: this.$t('_validation.fixFormErrors')
                });
                return
            }

            return ApiNewOrder.startProduction(this.agreementLineId, { schedule: this.payload })
                .then(() => {
                    // this.line.productions = Array.isArray(data) ? data : [];
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
            Object.keys(this.form).forEach(slug => {
                this.form[slug].forEach((field, fieldIdx) => {
                    if (field.id === 'dateStart') {
                        this.form[slug][fieldIdx].value = this.getDefaultStartDate(slug, this.confirmedDate)
                    }
                    if (field.id === 'dateEnd') {
                        this.form[slug][fieldIdx].value = this.getDefaultEndDate(slug, new Date(this.confirmedDate.getTime()))
                    }
                })
            })
        },

        /**
         * @param {string} dpt
         * @param {Date} confirmedDate
         */
        getDefaultStartDate(dpt, confirmedDate)
        {
            return (new Date()).toISOString().split('T')[0]
        },

        /**
         * @param {string} dpt
         * @param {Date} confirmedDate
         */
        getDefaultEndDate(dpt, confirmedDate)
        {
            let date = confirmedDate
            if (dpt === DPT_GLUEING) {
                let daysPassed = 0
                while (daysPassed < 5) {
                    date = new Date(date.setDate(date.getDate()-1))
                    const weekDay = date.getDay()
                    if (![0,6].includes(weekDay)) {
                        daysPassed++
                    }
                }
            }
            return getLocalDate(date)
        },

    },

    data: () => ({
        form: getForm()
    })
}

function getForm() {
    return helpers.getDepartments()
        .reduce((prev, current) => {
            prev[current.slug] = [
                {
                    id: 'dateStart',
                    value: null,
                    type: 'date',
                    label: 'agreement_line_list.startProductionForm.startDate'
                },
                {
                    id: 'dateEnd',
                    value: null,
                    type: 'date',
                    label: 'agreement_line_list.startProductionForm.endDate'
                }
            ]
            return prev
        }, {})
}

</script>

<style scoped lang="scss">

</style>