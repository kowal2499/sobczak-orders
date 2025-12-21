<script>
import proxyValue from '@/mixins/proxyValue'
import { getUserDepartments } from '@/helpers'

export default {
    name: "AdjustmentRatioForm",
    props: {
        factor: {
            type: Number,
        }
    },
    mixins: [proxyValue],
    computed: {
        departments() {
            return getUserDepartments(this.departmetns)
        }
    }
}
</script>

<template>
    <div>
        <b-form-group
            :label="'Modyfikatory współcznnika bazowego dla działów'"
            label-cols-md="3"
        >
            <div class="d-flex gap-3" v-for="dpt in departments" :key="dpt.slug">
                <b-form-group
                    :label="dpt.name"
                >
                    <b-form-checkbox
                        :unchecked-value="false"
                        switch
                        v-model="value[dpt.slug].active"
                    />
                </b-form-group>

                <b-form-group
                    v-if="value[dpt.slug].active"
                    :label="'Wartość modyfikatora (%)'"
                    :description="'Procentowa modyfikacja współczynnika bazowego, zakres 0-100%'"
                >
                    <b-form-input type="number" :min="0" :max="100" v-model="value[dpt.slug].value" />
                </b-form-group>

                <b-form-group
                    class="ml-auto d-flex justify-content-end"
                    :label="'Wartość współczynnika dla działu ' + dpt.name"
                >
                    <b-form-input type="number" disabled
                                  style="width: 100px"
                                  class="text-right"
                                  :value="value[dpt.slug].active
                                  ? (factor || 0) * (value[dpt.slug].value || 0) / 100
                                  : factor"
                    />
                </b-form-group>
            </div>
        </b-form-group>

    </div>
</template>

<style scoped>

</style>