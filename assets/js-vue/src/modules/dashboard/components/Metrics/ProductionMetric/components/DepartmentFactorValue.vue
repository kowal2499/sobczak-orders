<script>
import { defineComponent } from 'vue'
import { getFactorName, getFactorValue } from '../../../../services/FactorHelper'
export default defineComponent({
    name: 'DepartmentFactorValue',
    props: {
        factorData: {
            type: Object,
            validator: (val) => Object.hasOwn(val, 'factor') && Object.hasOwn(val, 'factorsStack'),
        },
        noStatusIcon: {
            type: Boolean,
            default: false,
        }
    },
    computed: {
        popoverTarget() {
            return 'popover-' + this._uid
        }
    },
    methods: {
        getName(source, value) {
            return getFactorName(source, value)
        },
        getValue(source, value) {
            return getFactorValue(source, value)
        }
    }
})
</script>

<template>
    <div class="d-flex gap-1">
        <div class="d-flex justify-content-center align-items-center gap-1">
            <div :class="factorData.factor === null ? 'text-muted' : 'font-weight-bold'">{{ Math.round(factorData.factor * 100) / 100 }}</div>
            <font-awesome-icon
                v-if="!noStatusIcon"
                :icon="factorData.factor === null ? 'times' : 'check-circle'"
                :class="[factorData.factor === null ? 'text-danger' : 'text-success', 'opacity-75']"
                style="font-size: 18px"
            />
        </div>
        <div v-if="factorData.factorsStack.length" class="text-center">
            <font-awesome-icon icon="info-circle" class="opacity-25 text-primary" :id="popoverTarget"/>
            <b-popover v-if="factorData.factorsStack.length" custom-class="factor-data-popover" :target="popoverTarget" placement="bottom" triggers="hover">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th class="border-top-0">Nazwa źródła</th>
                            <th class="border-top-0">Wartość</th>
                            <th class="border-top-0">Opis</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(item, key) in factorData.factorsStack" :key="key">
                            <td>
                                {{ getName(item.source, item.value) }}
                            </td>
                            <td>
                                {{ getValue(item.source, item.value) }}
                            </td>
                            <td>
                                {{ item.description || '-' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </b-popover>
        </div>
    </div>
</template>

<style lang="scss">
.factor-data-popover {
    min-width: 350px;
    .popover-body {
        padding: 0;
    }
}
</style>