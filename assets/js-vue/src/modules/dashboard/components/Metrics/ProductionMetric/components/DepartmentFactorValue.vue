<script>
import { defineComponent } from 'vue'

export default defineComponent({
    name: 'DepartmentFactorValue',
    props: {
        factorData: {
            type: Object,
            validator: (val) => Object.hasOwn(val, 'factor') && Object.hasOwn(val, 'factorsStack'),
        },
    },
    computed: {
        popoverTarget() {
            return 'popover-' + this._uid
        }
    },
    methods: {
        getName(source, value) {
            switch (source) {
                case 'agreement_line':
                    return 'Podstawa zamówienia'
                case 'factor_adjustment_bonus':
                    return value > 0 ? 'Bonus' : 'Kara'
                case 'factor_adjustment_ratio':
                    return 'Waga podstawy'
                default:
                    return 'Nieobsłużona wartość'
            }
        },
        getValue(source, value) {
            switch (source) {
                case 'factor_adjustment_ratio':
                    return String(parseFloat(value * 100).toFixed(1)).concat('%')
                default:
                    return parseFloat(value).toFixed(1)
            }
        }
    }
})
</script>

<template>
    <div>
        <font-awesome-icon
            v-if="!factorData.factor && !factorData.factorsStack.length"
            icon="times"
            class="text-danger opacity-75"
            style="font-size: 20px"
        />
        <span v-else :class="!factorData.factor ? 'text-muted' : 'font-weight-bold'">{{ factorData.factor }}</span>
        <template v-if="factorData.factorsStack.length">
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
        </template>


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