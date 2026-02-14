<template>
    <b-form-group
        :label="$t('agreement_line_list.factorsForm.adjustmentBonusSectionTitle')"
        label-cols-md="3"
        label-class="pt-0"
    >
        <div class="d-flex justify-content-start mb-3">
            <button class="btn btn-primary btn-sm" @click="addRow">{{ $t('_add') }}</button>
        </div>
        <b-row v-for="(record, idx) in proxyData">

            <b-col cols="6" md="3">
                <FormLayout rules="required|excluded:0"
                            :label="$t('agreement_line_list.factorsForm.bonusValue')"
                            :form-group-config="{
                                description: $t('agreement_line_list.factorsForm.adjustmentBonusValueDescription')
                            }"
                            no-label
                            #default="{ state }"
                >
                    <b-form-input
                        type="number"
                        :placeholder="$t('agreement_line_list.factorsForm.bonusValue')"
                        v-model="record.value"
                        :state="state"
                    />
                </FormLayout>
            </b-col>
            <b-col cols="6" md="3">
                <FormLayout rules="required" :label="$t('_department')" no-label #default="{ state }">
                    <b-form-select
                        v-model="record.departmentSlug"
                        :state="state"
                    >
                        <b-form-select-option :value="null">{{ $t('_department') }}</b-form-select-option>
                        <b-form-select-option v-for="dept in departments" :key="dept.slug" :value="dept.slug">
                            {{ dept.name }}
                        </b-form-select-option>
                    </b-form-select>
                </FormLayout>
            </b-col>
            <b-col cols="12" md="4">
                <FormLayout rules="required" :label="$t('_description')" no-label #default="{ state }">
                    <b-form-textarea
                        type="text"
                        v-model="record.description"
                        :placeholder="$t('_description')"
                        :state="state"
                        rows="2"
                    />
                </FormLayout>
            </b-col>
            <b-col cols="12" md="2" class="text-right">
                <button class="btn btn-danger btn-sm" @click="onDeleteRow(idx)">Usuń</button>
            </b-col>
        </b-row>

    </b-form-group>
</template>

<script>
import proxyValue from '@/mixins/proxyValue'
import { getUserDepartments } from '@/helpers'

export default {
    name: "AdjustmentRatioForm",
    mixins: [proxyValue],
    computed: {
        departments() {
            return getUserDepartments()
        }
    },
    methods: {
        addRow() {
            this.proxyData.push({
                id: null,
                description: null,
                value: 0,
                departmentSlug: null,
                source: 'factor_adjustment_bonus'
            })
        },
        onDeleteRow(idx) {
            this.proxyData.splice(idx, 1)
        }
    },
    data: () => ({

    })
}
</script>

<style scoped>

</style>