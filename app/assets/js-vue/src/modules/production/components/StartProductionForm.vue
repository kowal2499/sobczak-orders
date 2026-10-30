<template>
    <div class="production-dates">
        <b-row class="production-dates__head d-none d-lg-flex">
            <b-col :lg="4">{{ $t('agreement_line_list.startProductionForm.department') }}</b-col>
            <b-col :lg="4">{{ $t('agreement_line_list.startProductionForm.startDate') }}</b-col>
            <b-col :lg="4">{{ $t('agreement_line_list.startProductionForm.endDate') }}</b-col>
        </b-row>

        <b-row v-for="row in formProxy" :key="row.slug" class="production-dates__row">
            <b-col :cols="12" :lg="4" class="production-dates__department">{{ getDepartmentName(row.slug) }}</b-col>

            <b-col :cols="6" :lg="4">
                <ValidationProvider
                    :name="`${row.slug}.dateStart`"
                    #default="{ errors }"
                    :rules="{
                        required: true,
                        dateFromOrEqual: { target: row.dateEnd },
                        dateEarlierThan: { deadline: confirmedDate }
                    }"
                >
                    <b-form-group
                        :label="$t('agreement_line_list.startProductionForm.startDate')"
                        label-class="d-lg-none"
                        :invalid-feedback="errors.join(' ')"
                        class="mb-0"
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

            <b-col :cols="6" :lg="4">
                <ValidationProvider
                    :name="`${row.slug}.dateEnd`"
                    #default="{ errors }"
                    :rules="{
                        required: true,
                        dateToOrEqual: { target: row.dateStart },
                        dateEarlierThan: { deadline: confirmedDate }
                    }"
                >
                    <b-form-group
                        :label="$t('agreement_line_list.startProductionForm.endDate')"
                        label-class="d-lg-none"
                        :invalid-feedback="errors.join(' ')"
                        class="mb-0"
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

import { getDepartmentName } from "@/helpers";
import datePicker from "@/components/base/DatePicker.vue";

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
.production-dates {
    &__head {
        padding-bottom: 0.35rem;
        border-bottom: 1px solid var(--colorRule);
        font-size: 0.85rem;
        font-weight: 500;
        color: var(--colorInkMuted);
    }

    &__row {
        padding: 0.5rem 0;
        border-bottom: 1px solid var(--colorRule);
    }

    &__department {
        display: flex;
        align-items: center;
        font-weight: 500;
        color: var(--colorInkStrong);

        @media (max-width: 991.98px) {
            margin-bottom: 0.25rem;
        }
    }
}
</style>
