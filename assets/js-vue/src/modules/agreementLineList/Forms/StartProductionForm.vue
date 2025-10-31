<template>
    <div>
        <b-row v-for="row in formProxy" :key="row.slug">
            <b-col class="d-flex justify-content-end align-items-center">{{ getDepartmentName(row.slug) }}</b-col>

            <b-col>
                <ValidationProvider
                    :name="`${row.slug}.dateStart`"
                    #default="{ errors }"
                    :rules="{
                        required: true,
                        dateFrom: { target: row.dateEnd },
                        dateEarlierThan: { deadline: confirmedDate }
                    }"
                >
                    <b-form-group
                        :label="$t('agreement_line_list.startProductionForm.startDate')"
                        :invalid-feedback="errors.join(' ')"
                    >
                        <date-picker
                            v-model="row.dateStart"
                            :is-range="false"
                            :date-only="true"
                            style="width: 100%"
                            :class="errors.length > 0 && 'is-invalid'"
                        />
                    </b-form-group>
                </ValidationProvider>
            </b-col>

            <b-col>
                <ValidationProvider
                    :name="`${row.slug}.dateEnd`"
                    #default="{ errors }"
                    :rules="{
                        required: true,
                        dateTo: { target: row.dateStart },
                        dateEarlierThan: { deadline: confirmedDate }

                    }"
                >
                    <b-form-group
                        :label="$t('agreement_line_list.startProductionForm.endDate')"
                        :invalid-feedback="errors.join(' ')"
                    >
                        <date-picker
                            v-model="row.dateEnd"
                            :is-range="false"
                            :date-only="true"
                            style="width: 100%"
                            :class="errors.length > 0 && 'is-invalid'"
                        />
                    </b-form-group>
                </ValidationProvider>
            </b-col>
        </b-row>
    </div>
</template>

<script>

import { getDepartmentName } from "../../../helpers";
import datePicker from "../../../components/base/DatePicker.vue";

export default {
    name: "StartProductionForm",

    props: {
        value: {
            type: Array,
            default: () => []
        },
        confirmedDate: {
            type: Date,
            required: true
        }
    },

    components: {
        datePicker
    },

    watch: {
        value: {
            immediate: true,
            deep: true,
            handler() {
                const valueStr = JSON.stringify(this.value)
                if (valueStr === JSON.stringify(this.formProxy)) {
                    return
                }
                this.formProxy =  JSON.parse(valueStr)
            }
        },

        formProxy: {
            deep: true,
            handler() {
                this.$emit('input', JSON.parse(JSON.stringify(this.formProxy)))
            }
        }
    },

    methods: {
        getDepartmentName,
    },

    data: () => ({
        formProxy: []
    })
}
</script>



<style scoped lang="scss">

</style>