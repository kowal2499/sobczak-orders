<template>
    <modal-action title="Daty produkcji dla działów" :configuration="{ hideFooter: false, size: 'lg' }">
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
                <button class="btn btn-success ml-2" @click="startProduction(close)">
                    <i class="fa fa-play mr-2" aria-hidden="true" /> Rozpocznij produkcję
                </button>
            </div>
        </template>

        <template #default="{ close }">
            <b-row v-for="slug in Object.keys(form)" :key="slug">
                <b-col class="d-flex justify-content-end align-items-center">{{ getDepartmentName(slug) }}</b-col>
                <b-col v-for="(field, fieldIdx) in form[slug]"
                       :key="fieldIdx"
                >
                    <b-form-group
                        :label="$t(field.label)"
                    >
                        <date-picker
                            v-if="field.type === 'date'"
                            v-model="form[slug][fieldIdx].value"
                            :is-range="false"
                            :date-only="true"
                            style="width: 100%"
                        />
                    </b-form-group>
                </b-col>
            </b-row>
        </template>
    </modal-action>
</template>

<script>
import ModalAction from "../../../components/base/ModalAction.vue";
import ApiNewOrder from "../../../api/neworder";
import helpers, { getDepartmentName, getLocalDate, DPT_GLUEING } from "../../../helpers";
import datePicker from "../../../components/base/DatePicker.vue";
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
        datePicker
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
        startProduction(closeCallback)
        {
            return ApiNewOrder.startProduction(this.agreementLineId, { schedule: this.payload })
                .then(({data}) => {
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
                let currentDate = date
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

        getDepartmentName
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
                    label: 'Data rozpoczęcia'
                },
                {
                    id: 'dateEnd',
                    value: null,
                    type: 'date',
                    label: 'Data zakończenia'
                }
            ]
            return prev
        }, {})
}

</script>

<style scoped lang="scss">

</style>