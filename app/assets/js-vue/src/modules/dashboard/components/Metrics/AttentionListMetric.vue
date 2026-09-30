<script>
import MetricLayout from './MetricLayout.vue'
import AgreementLineHeading from '@/components/base/Showcase/AgreementLineHeading.vue'
import { statuses } from '@/helpers'

const KINDS = ['overdueOrders', 'unplannedOrders', 'notStartedProductions', 'overdueProductions']

export default {
    name: 'AttentionListMetric',
    components: { MetricLayout, AgreementLineHeading },
    props: {
        data: {
            type: Array,
            default: null
        },
        isBusy: {
            type: Boolean,
            default: false
        },
        kind: {
            type: String,
            required: true,
            validator: value => KINDS.includes(value),
        },
    },
    computed: {
        items() {
            return this.data || []
        },
        isProductionList() {
            return this.kind === 'notStartedProductions' || this.kind === 'overdueProductions'
        },
    },
    methods: {
        departmentName(slug) {
            return this.$t(`_${slug}`)
        },
        statusOf(production) {
            return statuses.find(s => s.value === parseInt(production.status)) || { name: '-', color: '#fff' }
        },
    },
}
</script>

<template>
    <MetricLayout :is-busy="isBusy" pinned-title class="border-left-warning">
        <template #title>
            {{ $t(`dashboard.attention.${kind}.title`) }}
            <span v-if="items.length" class="attention-count">{{ items.length }}</span>
        </template>

        <template #description>
            <p>{{ $t(`dashboard.attention.${kind}.description`) }}</p>
        </template>

        <ul v-if="items.length" class="attention-list">
            <li v-for="item in items" :key="item.agreementLineId" class="attention-item">
                <AgreementLineHeading
                    compact
                    :agreement-line-id="item.agreementLineId"
                    :order-number="item.orderNumber"
                    :customer-name="(item.customerName || '').trim()"
                    :product-name="(item.productName || '').trim()"
                    :status="item.status"
                    :panel-department="isProductionList ? item.productions[0]?.departmentSlug : null"
                >
                    <template v-if="kind === 'overdueOrders'" #aside>
                        <span class="attention-days attention-days--late">
                            {{ $tc('dashboard.attention.daysAfterDeadline', item.days, { n: item.days }) }}
                        </span>
                    </template>
                    <template v-else-if="kind === 'unplannedOrders'" #aside>
                        <span class="attention-days">
                            {{ $tc('dashboard.attention.daysWaiting', item.days, { n: item.days }) }}
                        </span>
                    </template>
                </AgreementLineHeading>

                <div v-if="kind === 'overdueOrders' && item.productions.length" class="attention-departments">
                    <span
                        v-for="production in item.productions"
                        :key="production.departmentSlug"
                        v-b-tooltip.hover
                        class="attention-department"
                        :title="statusOf(production).name"
                        :style="{ backgroundColor: statusOf(production).color }"
                    >{{ departmentName(production.departmentSlug) }}</span>
                </div>

                <template v-if="isProductionList">
                    <div
                        v-for="production in item.productions"
                        :key="production.departmentSlug"
                        class="attention-facts"
                    >
                        <span class="attention-fact attention-fact--department">
                            {{ departmentName(production.departmentSlug) }}
                        </span>
                        <span class="attention-days attention-days--late">
                            {{ $tc(`dashboard.attention.${kind}.days`, production.daysLate, { n: production.daysLate }) }}
                        </span>
                    </div>
                </template>
            </li>
        </ul>
        <div v-else-if="!isBusy" class="attention-empty">
            {{ $t('dashboard.attention.empty') }}
        </div>
    </MetricLayout>
</template>

<style scoped lang="scss">
@use '../../../../components/base/Showcase/tokens' as *;

.attention-count {
    display: inline-block;
    margin-left: 0.35rem;
    padding: 0 0.45rem;
    border-radius: 10rem;
    background-color: rgba(var(--colorPrimaryRgb), 0.1);
    font-variant-numeric: tabular-nums;
}

.attention-list {
    list-style: none;
    margin: 0;
    padding: 0;
    font-size: 0.8rem;
    color: $showcase-ink;
}

.attention-item {
    padding: 0.6rem 0;
    border-bottom: 1px solid $showcase-rule;

    &:first-child {
        padding-top: 0.25rem;
    }

    &:last-child {
        border-bottom: 0;
    }
}

.attention-facts {
    display: flex;
    flex-wrap: wrap;
    align-items: baseline;
    column-gap: 1rem;
    margin-top: 0.3rem;
}

.attention-fact {
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}

.attention-fact--department {
    min-width: 6.5rem;
    color: $showcase-ink-strong;
    font-weight: 500;
}

.attention-days {
    margin-left: auto;
    color: $showcase-ink-strong;
    font-weight: 600;
    white-space: nowrap;

    &--late {
        color: #e74a3b;
    }
}

.attention-departments {
    display: flex;
    flex-wrap: wrap;
    gap: 0.3rem;
    margin-top: 0.4rem;
}

.attention-department {
    padding: 0.05rem 0.45rem;
    border: 1px solid $showcase-rule;
    border-radius: 0.25rem;
    color: $showcase-ink-strong;
    font-size: 0.75rem;
    cursor: default;
}

.attention-empty {
    color: $showcase-muted;
    font-size: 0.85rem;
}
</style>
