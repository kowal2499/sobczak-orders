<script>
import { defineComponent } from 'vue'
import StatusIcon from './StatusIcon.vue'
import PlainFactorCell from './FactorCell/PlainFactorCell.vue';
import OrderFactorCell from './FactorCell/OrderFactorCell.vue';
import AgreementLineHeading from '@/components/base/Showcase/AgreementLineHeading.vue';

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
    name: 'Details',
    components: {AgreementLineHeading, OrderFactorCell, PlainFactorCell, StatusIcon },
    props: {
        data: {
            type: Array,
            default: () => []
        },
        // komórka raportu "Ukończone zadania produkcyjne" - z datami produkcji i wariantem "poza zakresem"
        detailedCells: {
            type: Boolean,
            default: false,
        },
        // { start: 'YYYY-MM-DD', end: 'YYYY-MM-DD' } - zakres raportu, dla dymka "poza zakresem dat"
        reportRange: {
            type: Object,
            default: () => ({ start: null, end: null })
        },
    },
    computed: {
        cellComponent() {
            return this.detailedCells ? 'PlainFactorCell' : 'OrderFactorCell'
        },
        cellProps() {
            // szczegóły zamówień nie mają zakresu raportu ani wariantu "poza zakresem"
            return this.detailedCells ? { reportRange: this.reportRange } : {}
        },
        rows() {
            return this.data.map(record => {
                return {
                    context: {
                        id: record.id,
                        orderNumber: record.orderNumber,
                        customer: record.customerName,
                        product: record.productName,
                        status: record.status,
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
                    key: 'factor',
                    label: this.$t('_factor'),
                    class: 'data-cell',
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
                .map(field => ({ ...field, tdClass: 'align-middle' }))
        },
    },
    data: () => ({})
})
</script>

<template>
    <SectionBlock class="details-block">
        <b-table
             responsive
             :items="rows"
             :fields="fields"
             sticky-header
             small
             hover
             no-border-collapse
             class="metric-details-table mb-0"
        >
            <template #cell(context)="{item}">
                <AgreementLineHeading
                    v-if="item.context"
                    compact
                    :agreement-line-id="item.context.id"
                    :order-number="(item.context.orderNumber || '').trim()"
                    :customer-name="(item.context.customer || '').trim()"
                    :product-name="(item.context.product || '').trim()"
                    :status="item.context.status"
                />
            </template>
    
            <template #cell(dpt01)="{item}">
                <component :is="cellComponent" :factorData="item.dpt01" v-bind="cellProps" />
            </template>
            <template #cell(dpt02)="{item}">
                <component :is="cellComponent" :factorData="item.dpt02" v-bind="cellProps" />
            </template>
            <template #cell(dpt03)="{item}">
                <component :is="cellComponent" :factorData="item.dpt03" v-bind="cellProps" />
            </template>
            <template #cell(dpt04)="{item}">
                <component :is="cellComponent" :factorData="item.dpt04" v-bind="cellProps" />
            </template>
            <template #cell(dpt05)="{item}">
                <component :is="cellComponent" :factorData="item.dpt05" v-bind="cellProps" />
            </template>
            <template #cell(dpt06)="{item}">
                <component :is="cellComponent" :factorData="item.dpt06" v-bind="cellProps" />
            </template>
    
            <template #head(context)="data">
                {{data.label}}
                <span class="column-total">{{ rows.length }}</span>
            </template>
            <template #head(factor)="data">
                {{data.label}}
                <span class="column-total">{{ footer.factor | roundFloat }}</span>
            </template>
            <template #head(dpt01)="data">
                {{data.label}}
                <span class="column-total">{{ footer.dpt01 | roundFloat }}</span>
            </template>
            <template #head(dpt02)="data">
                {{data.label}}
                <span class="column-total">{{ footer.dpt02 | roundFloat }}</span>
            </template>
            <template #head(dpt03)="data">
                {{data.label}}
                <span class="column-total">{{ footer.dpt03 | roundFloat }}</span>
            </template>
            <template #head(dpt04)="data">
                {{data.label}}
                <span class="column-total">{{ footer.dpt04 | roundFloat }}</span>
            </template>
            <template #head(dpt05)="data">
                {{data.label}}
                <span class="column-total">{{ footer.dpt05 | roundFloat }}</span>
            </template>
            <template #head(dpt06)="data">
                {{data.label}}
                <span class="column-total">{{ footer.dpt06 | roundFloat }}</span>
            </template>
        </b-table>
    </SectionBlock>
</template>
