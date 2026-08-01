<script>
import { defineComponent } from 'vue'
import OnTimeDepartmentValue from './OnTimeDepartmentValue.vue'

/**
 * Tabela szczegółów raportu premii "w terminie": działy w kolumnach, zlecenia w wierszach.
 * Wariant Details.vue używający dedykowanej komórki OnTimeDepartmentValue (wartość + terminowość).
 */
const DEFAULT_ROW = () => ({
    context: null,
    factor: 0,
    dpt01: 0,
    dpt02: 0,
    dpt03: 0,
    dpt04: 0,
    dpt05: 0,
    dpt06: 0,
})

export default defineComponent({
    name: 'OnTimeDetails',
    components: { OnTimeDepartmentValue },
    props: {
        data: {
            type: Array,
            default: () => []
        },
        height: [String, Number],
        // { start: 'YYYY-MM-DD', end: 'YYYY-MM-DD' } — zakres raportu dla popovera "poza zakresem dat"
        reportRange: {
            type: Object,
            default: () => ({ start: null, end: null })
        },
    },
    methods: {
        panelUrl(id) {
            return `/agreement/line/${id}`;
        },
    },
    computed: {
        rows() {
            return this.data.map(record => {
                return {
                    context: {
                        id: record.id,
                        orderNumber: record.orderNumber,
                        customer: record.customerName,
                        product: record.productName
                    },
                    factor: record.factor,
                    dpt01: record.involved_dpt01,
                    dpt02: record.involved_dpt02,
                    dpt03: record.involved_dpt03,
                    dpt04: record.involved_dpt04,
                    dpt05: record.involved_dpt05,
                    dpt06: record.involved_dpt06,
                }
            })
        },
        footer() {
            const summaryRow = DEFAULT_ROW();
            this.rows.forEach(row => {
                let factor = row.factor || 0;
                summaryRow.factor += factor;
                summaryRow.dpt01 += row.dpt01.factor || 0;
                summaryRow.dpt02 += row.dpt02.factor || 0;
                summaryRow.dpt03 += row.dpt03.factor || 0;
                summaryRow.dpt04 += row.dpt04.factor || 0;
                summaryRow.dpt05 += row.dpt05.factor || 0;
                summaryRow.dpt06 += row.dpt06.factor || 0;
            })
            return summaryRow;
        },
        fields() {
            return [
                {
                    key: 'context',
                    label: this.$t('_agreement_line'),
                    active: true,
                },
                {
                    key: 'dpt01',
                    label: this.$t('_dpt01'),
                    class: 'data-cell',
                    active: this.$user.can('production.show.gluing')
                },
                {
                    key: 'dpt02',
                    label: this.$t('_dpt02'),
                    class: 'data-cell',
                    active: this.$user.can('production.show.cnc')
                },
                {
                    key: 'dpt06',
                    label: this.$t('_dpt06'),
                    class: 'data-cell',
                    active: this.$user.can('production.show.intorex')
                },
                {
                    key: 'dpt03',
                    label: this.$t('_dpt03'),
                    class: 'data-cell',
                    active: this.$user.can('production.show.grinding')
                },
                {
                    key: 'dpt04',
                    label: this.$t('_dpt04'),
                    class: 'data-cell',
                    active: this.$user.can('production.show.laquering')
                },
                {
                    key: 'dpt05',
                    label: this.$t('_dpt05'),
                    class: 'data-cell',
                    active: this.$user.can('production.show.packing')
                },
            ].filter(field => field.active)
        },
    },
    data: () => ({})
})
</script>

<template>
    <b-table
         responsive
         :items="rows"
         :fields="fields"
         :sticky-header="`${height}px`"
         small
         hover
         no-border-collapse
         class="orders-count-table"
    >
        <template #cell(context)="{item}">
            <div v-if="item.context">
                <div class="context-row">
                    <font-awesome-icon size="sm" icon="user" />
                    <span>{{ (item.context.customer || '').trim() }}</span>
                </div>
                <div class="context-row">
                    <font-awesome-icon size="sm" icon="shopping-cart" />
                    <span>{{ (item.context.product || '').trim() }}</span>
                </div>
                <div class="context-row">
                    <font-awesome-icon size="sm" icon="hashtag" />
                    <span>{{ (item.context.orderNumber || '').trim() }}</span>
                </div>
                <div class="context-row" v-if="$user.can('production.panel')">
                    <a :href="panelUrl(item.context.id)" target="_blank" class="text-decoration-none">
                        <font-awesome-icon size="sm" icon="link" />
                        <span>{{ $t('_agreement_line_panel') }}</span>
                    </a>
                </div>
            </div>
        </template>

        <template #cell(dpt01)="{item}">
            <OnTimeDepartmentValue
                :factorData="item.dpt01"
                :report-range="reportRange"
                :agreement-line-id="item.context && item.context.id"
                @saved="$emit('saved')"
            />
        </template>
        <template #cell(dpt02)="{item}">
            <OnTimeDepartmentValue
                :factorData="item.dpt02"
                :report-range="reportRange"
                :agreement-line-id="item.context && item.context.id"
                @saved="$emit('saved')"
            />
        </template>
        <template #cell(dpt03)="{item}">
            <OnTimeDepartmentValue
                :factorData="item.dpt03"
                :report-range="reportRange"
                :agreement-line-id="item.context && item.context.id"
                @saved="$emit('saved')"
            />
        </template>
        <template #cell(dpt04)="{item}">
            <OnTimeDepartmentValue
                :factorData="item.dpt04"
                :report-range="reportRange"
                :agreement-line-id="item.context && item.context.id"
                @saved="$emit('saved')"
            />
        </template>
        <template #cell(dpt05)="{item}">
            <OnTimeDepartmentValue
                :factorData="item.dpt05"
                :report-range="reportRange"
                :agreement-line-id="item.context && item.context.id"
                @saved="$emit('saved')"
            />
        </template>
        <template #cell(dpt06)="{item}">
            <OnTimeDepartmentValue
                :factorData="item.dpt06"
                :report-range="reportRange"
                :agreement-line-id="item.context && item.context.id"
                @saved="$emit('saved')"
            />
        </template>

        <template #head(context)="data">
            {{data.label}}
            <br><b-badge pill variant="light">{{ rows.length }}</b-badge>
        </template>
        <template #head(dpt01)="data">
            {{data.label}}
            <br><b-badge pill variant="light">{{ footer.dpt01 | roundFloat }}</b-badge>
        </template>
        <template #head(dpt02)="data">
            {{data.label}}
            <br><b-badge pill variant="light">{{ footer.dpt02 | roundFloat }}</b-badge>
        </template>
        <template #head(dpt03)="data">
            {{data.label}}
            <br><b-badge pill variant="light">{{ footer.dpt03 | roundFloat }}</b-badge>
        </template>
        <template #head(dpt04)="data">
            {{data.label}}
            <br><b-badge pill variant="light">{{ footer.dpt04 | roundFloat }}</b-badge>
        </template>
        <template #head(dpt05)="data">
            {{data.label}}
            <br><b-badge pill variant="light">{{ footer.dpt05 | roundFloat }}</b-badge>
        </template>
        <template #head(dpt06)="data">
            {{data.label}}
            <br><b-badge pill variant="light">{{ footer.dpt06 | roundFloat }}</b-badge>
        </template>
    </b-table>
</template>

<style lang="scss">
.orders-count-table {
    font-size: 0.75em;
    font-weight: normal;
    padding-top: 0 !important;

    td {
        vertical-align: middle
    }

    th {
        color: #4e73df !important;
    }

    th.data-cell,
    td.data-cell {
        text-align: center;
        width: 100px;
    }

    .context-row {
        display: flex;
        align-items: baseline;
        justify-content: flex-start;

        svg {
            color: #CCC;
        }
        a {
            font-size: 0.8em;
        }
        span {
            padding-left: 10px;
            white-space: pre-line;
        }
    }
}
</style>
