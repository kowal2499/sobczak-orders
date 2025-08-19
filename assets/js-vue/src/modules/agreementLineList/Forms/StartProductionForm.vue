<template>
    <div>
        <b-row v-for="slug in Object.keys(formProxy)" :key="slug">
            <b-col class="d-flex justify-content-end align-items-center">{{ getDepartmentName(slug) }}</b-col>
            <b-col v-for="(field, fieldIdx) in formProxy[slug]"
                   :key="fieldIdx"
            >
                <ValidationProvider
                    #default="{ errors }"
                    :rules="{
                                    required: true,
                                    ...(fieldIdx === 0 && {
                                        dateFrom: {
                                            target: formProxy[slug][1].value
                                        }
                                    }),
                                    ...(fieldIdx === 1 && {
                                        dateTo: {
                                            target: formProxy[slug][0].value
                                        }
                                    })

                                }"
                    :name="`${slug}${field.id}`"
                >
                    <b-form-group
                        :label="$t(field.label)"
                        :invalid-feedback="errors.join(' ')"
                    >
                        <date-picker
                            v-model="formProxy[slug][fieldIdx].value"
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
            type: Object,
            default: () => {}
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
        formProxy: {}
    })
}
</script>



<style scoped lang="scss">

</style>