<script>
import SidebarLayout from '@/components/layout/SidebarLayout.vue'
import SidebarNavbar from '@/components/layout/SidebarNavbar.vue'
import AgreementLineRmShowcaseItem from './AgreementLineRmShowcaseItem.vue'

export default {
    name: 'AgreementLineShowcaseList',
    components: { SidebarLayout, SidebarNavbar, AgreementLineRmShowcaseItem },
    props: {
        lines: {
            type: Array,
            default: () => []
        },
        departments: {
            type: Array,
            default: null
        },
        exportable: {
            type: Boolean,
            default: false
        },
        // [{ label, value, hint? }] - wartości podsumowujące całą listę, nad wyszukiwarką
        summary: {
            type: Array,
            default: () => []
        },
    },
}
</script>

<template>
    <SidebarLayout>
        <template #header>
            <div class="showcase-list-header">
                <dl v-if="summary.length" class="showcase-summary">
                    <div v-for="(item, index) in summary" :key="index" class="showcase-summary-item">
                        <dt>{{ item.label }}</dt>
                        <dd>
                            {{ item.value }}
                            <span v-if="item.hint" class="showcase-summary-hint">{{ item.hint }}</span>
                        </dd>
                    </div>
                </dl>
                <SidebarNavbar
                    class="ml-auto"
                    :show-excel-export-btn="exportable"
                    @search="$emit('search', $event)"
                    @exportExcel="$emit('exportExcel')"
                />
            </div>
        </template>

        <template #content>
            <AgreementLineRmShowcaseItem
                v-for="(line, i) in lines"
                :key="`${line.agreementLineId}-${i}`"
                :data="line"
                :departments="departments"
            />
            <div v-if="!lines.length" class="text-muted text-center m-3">-</div>
        </template>
    </SidebarLayout>
</template>

<style scoped lang="scss">
@use './tokens' as *;

.showcase-list-header {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    justify-content: space-between;
    gap: 0.5rem 1rem;
    margin: 0 1rem;
    padding: 0.25rem 1rem 0.75rem;
    border-bottom: 1px solid $showcase-rule;
}

.showcase-summary {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem 2rem;
    margin: 0;
}

.showcase-summary-item {
    dt {
        font-size: 0.75rem;
        font-weight: 400;
        color: $showcase-muted;
        margin-bottom: 0.1rem;
    }

    dd {
        color: $showcase-primary;
        font-size: 1.05rem;
        font-weight: 600;
        font-variant-numeric: tabular-nums;
        margin: 0;
    }
}

.showcase-summary-hint {
    color: $showcase-ink;
    font-size: 0.85rem;
    font-weight: 400;
    margin-left: 0.25rem;
}
</style>
