<script>
import DepartmentFactorValue
    from "@/modules/dashboard/components/Metrics/ProductionMetric/components/DepartmentFactorValue.vue";
import Badge from '../../../components/production/Badge'

export default {
    name: "FactorDisplay",
    components: {
        Badge,
        DepartmentFactorValue
    },
    props: {
        factorData: {
            type: Object,
            validator: (val) => Object.hasOwn(val, 'factor') && Object.hasOwn(val, 'factorsStack'),
        },
    },
    computed: {
        hasBonus() {
            return this.factorData.factorsStack.some(item =>
                'factor_adjustment_bonus' === item.source && item.value > 0
            )
        },
        hasPenalty() {
            return this.factorData.factorsStack.some(item =>
                'factor_adjustment_bonus' === item.source && item.value < 0
            )
        }
    }
}
</script>

<template>
    <Badge variant="text">
        <template #icon>
            <div class="d-flex gap-1">
                {{ $t('orders.fctr') }}
                <i class="fa fa-thumbs-up text-success" v-if="hasBonus"></i>
                <i class="fa fa-thumbs-down text-danger" v-if="hasPenalty"></i>
            </div>
        </template>
        <template #message>
            <div class="d-flex justify-content-end">
                <department-factor-value
                    :factor-data="factorData"
                    no-status-icon
                />
            </div>
        </template>
    </Badge>
</template>

<style scoped lang="scss">

</style>