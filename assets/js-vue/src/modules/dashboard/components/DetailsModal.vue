<template>
    <b-modal v-model="valueProxy" :title="title" centered size="xl" @shown="init" ok-only>
        <font-awesome-icon v-if="busy" icon="spinner" spin/>

        <b-table v-if="!busy"
                 responsive
                 :items="rows"
                 :fields="fields"
                 small
                 hover
                 sticky-header="600px"
                 class="modal-table"
                 tbody-tr-class="modal-table-tr"
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
                    <div class="context-row">
                        <a :href="panelUrl(item.context.id)" target="_blank">
                            <font-awesome-icon size="sm" icon="link" />
                            <span>{{ $t('_agreement_line_panel') }}</span>
                        </a>
                    </div>
                </div>
            </template>

            <template #cell(dpt01)="{item}"><status-icon :tick-value="item.dpt01" /></template>
            <template #cell(dpt02)="{item}"><status-icon :tick-value="item.dpt02" /></template>
            <template #cell(dpt03)="{item}"><status-icon :tick-value="item.dpt03" /></template>
            <template #cell(dpt04)="{item}"><status-icon :tick-value="item.dpt04" /></template>
            <template #cell(dpt05)="{item}"><status-icon :tick-value="item.dpt05" /></template>

            <template #head(context)="data">
                {{data.label}}
                <br><b-badge pill variant="light">{{ rows.length }}</b-badge>
            </template>
            <template #head(factor)="data">
                {{data.label}}
                <br><b-badge pill variant="light">{{ footer.factor | roundFloat }}</b-badge>
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
        </b-table>

    </b-modal>
</template>

<script>

import StatusIcon from "./StatusIcon";

const DEFAULT_ROW = () => ({
    context: null,
    factor: 0,
    dpt01: 0,
    dpt02: 0,
    dpt03: 0,
    dpt04: 0,
    dpt05: 0,
})


export default {
    name: "DetailsModal",
    props: {
        value: Boolean,
        recordsPromise: Promise,
        title: String
    },
    components: {
        StatusIcon
    },
    methods: {
        init() {
            this.busy = true;
            this.recordsPromise
                .then(({data}) => {
                    this.data = data
                })
                .finally(() => this.busy = false)
        },
        panelUrl(id) {
            return `/agreement/line/${id}`;
        }
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
                    dpt01: parseInt(record.involved_dpt01) > 0,
                    dpt02: parseInt(record.involved_dpt02) > 0,
                    dpt03: parseInt(record.involved_dpt03) > 0,
                    dpt04: parseInt(record.involved_dpt04) > 0,
                    dpt05: parseInt(record.involved_dpt05) > 0,
                }
            })
        },
        footer() {
            const summaryRow = DEFAULT_ROW();
            this.rows.forEach(row => {
                let factor = row.factor || 0;
                summaryRow.factor += factor;
                summaryRow.dpt01 += row.dpt01 ? factor : 0;
                summaryRow.dpt02 += row.dpt02 ? factor : 0;
                summaryRow.dpt03 += row.dpt03 ? factor : 0;
                summaryRow.dpt04 += row.dpt04 ? factor : 0;
                summaryRow.dpt05 += row.dpt05 ? factor : 0;
            })
            return summaryRow;
        },
        fields() {
            return [
                {
                    key: 'context',
                    label: this.$t('_agreement_line')
                },
                {
                    key: 'factor',
                    label: this.$t('_factor'),
                    class: 'data-cell'
                },
                {
                    key: 'dpt01',
                    label: this.$t('_dpt01'),
                    class: 'data-cell'
                },
                {
                    key: 'dpt02',
                    label: this.$t('_dpt02'),
                    class: 'data-cell'
                },
                {
                    key: 'dpt03',
                    label: this.$t('_dpt03'),
                    class: 'data-cell'
                },
                {
                    key: 'dpt04',
                    label: this.$t('_dpt04'),
                    class: 'data-cell'
                },
                {
                    key: 'dpt05',
                    label: this.$t('_dpt05'),
                    class: 'data-cell'
                },
            ]
        },
        valueProxy: {
            get() {
                return this.value
            },
            set(v) {
                this.$emit('input', v)
            }
        }
    },
    data: () => ({
        busy: false,
        data: []
    })
}
</script>

<style lang="scss">
    .modal-table {
        font-size: 0.9em;
        td {
            vertical-align: middle
        }

        th {
            color: #4e73df !important;
            .badge {
                font-size: 13px;
            }
        }

        .data-cell {
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