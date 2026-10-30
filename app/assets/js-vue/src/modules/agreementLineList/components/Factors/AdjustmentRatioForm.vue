<script>
import proxyValue from '@/mixins/proxyValue'
import { DEPARTMENTS } from '@/helpers'

export default {
    name: "AdjustmentRatioForm",
    props: {
        factor: {
            type: Number,
        }
    },
    mixins: [proxyValue],
    methods: {
        computeDepartmentFactor(dptSlug) {
            let factor = this.factor || 0
            if (this.proxyData[dptSlug].active) {
                factor =  Math.round(factor * (this.proxyData[dptSlug].value || 0)) / 100
            }
            return factor
        },
        getDepartment(slug) {
            return DEPARTMENTS.find(dpt => dpt.slug === slug)
        },
        onActiveChange(itemKey, value) {
            if (!value) {
                this.proxyData[itemKey].value = 0
            }
        }
    },
}
</script>

<template>
    <div class="ratio-table">
        <div class="ratio-row ratio-row--head" aria-hidden="true">
            <div>{{ $t('_department') }}</div>
            <div>{{ $t('agreement_line_list.factorsForm.adjustmentRatioColumn') }}</div>
            <div class="text-right">{{ $t('agreement_line_list.factorsForm.factorPerDptColumn') }}</div>
        </div>

        <div v-for="itemKey in Object.keys(proxyData)" :key="itemKey" class="ratio-row">
            <b-form-checkbox
                :name="getDepartment(itemKey).name"
                :unchecked-value="false"
                switch
                v-model="proxyData[itemKey].active"
                @change="onActiveChange(itemKey, $event)"
            >
                {{ getDepartment(itemKey).name }}
            </b-form-checkbox>

            <FormLayout
                :label="$t('agreement_line_list.factorsForm.adjustmentRatioName')"
                :rules="proxyData[itemKey].active && 'required|numeric|min_value:0|max_value:100' || null"
                :form-group-config="{ class: 'mb-0' }"
                no-label
                #default="{ state }"
            >
                <b-form-input type="number"
                    :disabled="!proxyData[itemKey].active"
                    :min="0" :max="100"
                    v-model="proxyData[itemKey].value"
                    :state="state"
                    :aria-label="`${getDepartment(itemKey).name}: ${$t('agreement_line_list.factorsForm.adjustmentRatioName')}`"
                />
            </FormLayout>

            <div class="ratio-row__result">
                {{ computeDepartmentFactor(itemKey) }}
            </div>
        </div>
    </div>
</template>

<style scoped lang="scss">
.ratio-row {
    display: grid;
    grid-template-columns: minmax(8rem, 1fr) minmax(7rem, 12rem) minmax(5rem, 1fr);
    gap: 0.75rem;
    align-items: center;
    padding: 0.5rem 0;
    border-top: 1px solid var(--colorRule);

    &--head {
        padding-top: 0;
        border-top: 0;
        font-size: 0.8rem;
        color: var(--colorInkMuted);
    }
}

.ratio-row__result {
    text-align: right;
    font-size: 1rem;
    font-weight: 600;
    color: var(--colorPrimary);
}
</style>