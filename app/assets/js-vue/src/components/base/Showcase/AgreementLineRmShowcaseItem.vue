<script>
import { statuses, getUserDepartments, getDepartmentName, getLocalDate, orderDisplayNumber, agreementStatusesMap } from '@/helpers'
import DepartmentFactorValue
    from "@/modules/dashboard/components/Metrics/ProductionMetric/components/DepartmentFactorValue.vue";

export default {
    name: "AgreementLineRmShowcaseItem",
    components: {
        DepartmentFactorValue,
    },
    props: {
        data: {
            type: Object,
            default: () => ({})
        },
        departments: {
            type: Array,
            default: null
        },
    },
    computed: {
        isGhostOrder() {
            return Array.isArray(this.data.productions)
                && this.data.productions.length > 0
                && this.data.productions.every(p => p.isGhost === true)
        },
        orderNumber() {
            return orderDisplayNumber(this.data.orderNumber, this.data.internalNumber)
        },
        lineStatus() {
            return agreementStatusesMap[this.data.status] || null
        },
        fields() {
            return [
                { label: this.$t('_created_at'), value: this.data.agreementCreateDate },
                { label: this.$t('_confirmed_at'), value: this.data.confirmedDate },
                { label: this.$t('_factor'), value: this.$options.filters.roundFloat(this.data.factor, 2) },
                { label: this.$t('_created_by'), value: this.data.userName },
            ]
        },
        productionData() {
            if (!this.data.productions || !Array.isArray(this.data.productions)) {
                return [];
            }
            const userDepartments = getUserDepartments().map(d => d.slug);
            return this.data.productions
                .filter(prod => userDepartments.includes(prod.departmentSlug))
                .filter(prod => !this.departments || this.departments.includes(prod.departmentSlug))
                .map(prod => ({
                    ...prod,
                    rowKey: prod.id ?? prod.departmentSlug,
                    departmentName: getDepartmentName(prod.departmentSlug),
                    statusInfo: statuses.find(s => s.value === parseInt(prod.status)) || { name: prod.status, color: '#ccc' }
                }));
        },
    },
    methods: {
        panelUrl(id) {
            return `/agreement/line/${id}`;
        },
        formatDate(date) {
            return date ? getLocalDate(date) : '-'
        },
    },
}
</script>

<template>
    <div class="details showcase-item m-3" :class="{ 'ghost-order': isGhostOrder }">
        <div v-if="isGhostOrder" class="ghost-banner mb-2">
            <i class="fa fa-clock-o mr-1" aria-hidden="true"></i>
            {{ $t('dashboard.ghostOrderBanner') }}
        </div>

        <header class="showcase-header">
            <div class="showcase-header-main">
                <div class="showcase-title">
                    <span class="showcase-order-number">{{ orderNumber }}</span>
                    <span class="showcase-customer">{{ data.customerName }}</span>
                </div>
                <div class="showcase-subtitle">{{ data.productName }}</div>
            </div>
            <div class="showcase-header-aside">
                <span v-if="lineStatus" class="badge" :class="lineStatus.className">{{ lineStatus.name }}</span>
                <a
                    :href="panelUrl(data.agreementLineId)"
                    target="_blank"
                    class="showcase-panel-link"
                    @click.exact.prevent="$emit('open-panel', data.agreementLineId)"
                    :title="$t('_go_to_panel')"
                    :aria-label="$t('_go_to_panel')"
                >
                    <font-awesome-icon icon="link" />
                </a>
            </div>
        </header>

        <dl class="showcase-fields">
            <div v-for="(field, index) in fields" :key="index" class="showcase-field">
                <dt>{{ field.label }}</dt>
                <dd>{{ field.value || '-' }}</dd>
            </div>
        </dl>

        <div v-if="productionData.length" class="showcase-productions" role="table" :aria-label="$t('_department')">
            <div class="showcase-production showcase-production-head" role="row">
                <span role="columnheader">{{ $t('_department') }}</span>
                <span role="columnheader">{{ $t('_production_term') }}</span>
                <span role="columnheader">{{ $t('_factor') }}</span>
                <span role="columnheader" class="showcase-production-status">{{ $t('_production_status') }}</span>
            </div>
            <div v-for="prod in productionData" :key="prod.rowKey" class="showcase-production" role="row">
                <span class="showcase-production-name" role="cell">{{ prod.departmentName }}</span>
                <span class="showcase-production-dates" role="cell">
                    <span class="showcase-inline-label">{{ $t('_production_term') }}</span>
                    {{ formatDate(prod.dateStart) }}
                    <font-awesome-icon icon="arrow-right" class="showcase-arrow" aria-hidden="true" />
                    {{ formatDate(prod.dateEnd) }}
                </span>
                <span class="showcase-production-factor" role="cell">
                    <span class="showcase-inline-label">{{ $t('_factor') }}</span>
                    <DepartmentFactorValue v-if="prod.factorRatio" :factorData="prod.factorRatio" no-status-icon />
                    <span v-else>-</span>
                </span>
                <span class="showcase-production-status" role="cell">
                    <span class="badge font-weight-normal" :style="{ backgroundColor: prod.statusInfo.color }">
                        {{ prod.statusInfo.name }}
                    </span>
                </span>
            </div>
        </div>
    </div>
</template>

<style scoped lang="scss">
@use './tokens' as *;

$primary: $showcase-primary;
$ink-strong: $showcase-ink-strong;
$ink: $showcase-ink;
$muted: $showcase-muted;
$rule: $showcase-rule;
$production-columns: minmax(6rem, 1.1fr) minmax(11rem, 1.6fr) minmax(5.5rem, 0.8fr) 7.5rem;

.showcase-item {
    font-size: 0.85rem;
    color: $ink;
}

.ghost-order {
    background-color: rgba(179, 157, 219, 0.06);
}

.showcase-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid $rule;
}

.showcase-title {
    display: flex;
    flex-wrap: wrap;
    align-items: baseline;
    column-gap: 0.6rem;
    line-height: 1.3;
}

.showcase-order-number {
    color: $primary;
    font-size: 1.05rem;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
}

.showcase-customer {
    color: $ink-strong;
    font-size: 0.95rem;
    font-weight: 600;
}

.showcase-subtitle {
    color: $muted;
    margin-top: 0.15rem;
}

.showcase-header-aside {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-shrink: 0;
}

.showcase-panel-link {
    color: rgba(var(--colorPrimaryRgb), 0.75);
    border-radius: 4px;
    padding: 0.1rem 0.2rem;

    &:hover {
        color: $primary;
    }

    &:focus-visible {
        outline: 2px solid rgba(var(--colorPrimaryRgb), 0.5);
        outline-offset: 2px;
    }
}

.showcase-fields {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(9rem, 1fr));
    gap: 0.75rem 1rem;
    margin: 0.75rem 0 0;
}

.showcase-field {
    dt {
        font-size: 0.75rem;
        font-weight: 400;
        color: $muted;
        margin-bottom: 0.1rem;
    }

    dd {
        color: $ink-strong;
        font-weight: 500;
        margin: 0;
    }
}

.showcase-productions {
    margin-top: 1rem;
}

.showcase-production {
    display: grid;
    grid-template-columns: $production-columns;
    align-items: center;
    column-gap: 1rem;
    row-gap: 0.25rem;
    padding: 0.4rem 0.25rem;
    border-bottom: 1px solid $rule;

    &:last-child {
        border-bottom: 0;
    }
}

.showcase-production-head {
    color: $primary;
    font-size: 0.75rem;
    font-weight: 600;
    padding-top: 0;
    padding-bottom: 0.3rem;
    border-bottom: 2px solid rgba(var(--colorPrimaryRgb), 0.25);
}

.showcase-production-name {
    color: $ink-strong;
    font-weight: 500;
}

.showcase-production-dates {
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}

.showcase-arrow {
    color: rgba(var(--colorPrimaryRgb), 0.55);
    font-size: 0.7rem;
    margin: 0 0.3rem;
}

.showcase-production-factor {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;

    // DepartmentFactorValue pogrubia wartość w domyślnym (czarnym) kolorze tekstu
    ::v-deep .font-weight-bold {
        color: $ink-strong;
    }
}

.showcase-production-status {
    justify-self: end;

    .badge {
        color: $ink-strong;
    }
}

.showcase-inline-label {
    display: none;
    font-size: 0.75rem;
    color: $muted;
    margin-right: 0.35rem;
}

@media (max-width: 575.98px) {
    .showcase-production {
        grid-template-columns: 1fr auto;
    }

    .showcase-production-head {
        display: none;
    }

    .showcase-inline-label {
        display: inline;
    }

    .showcase-production-dates,
    .showcase-production-factor {
        grid-column: 1 / -1;
    }

    .showcase-production-status {
        grid-row: 1;
        grid-column: 2;
    }
}
</style>

