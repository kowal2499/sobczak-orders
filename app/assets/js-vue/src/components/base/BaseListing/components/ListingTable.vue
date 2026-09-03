<script>
import { StickyTableHeader } from "vh-sticky-table-header"
import Listing from "../model/Listing"

const ACTIONS_KEY = '__actions'

export default {
    name: "ListingTable",

    props: {
        listing: {
            type: Listing,
            required: true
        },

        items: {
            type: Array,
            default: () => ([])
        },

        loading: {
            type: Boolean,
            default: false
        },

        /** Klucz sortowania w formacie `pole_asc` / `pole_desc`. */
        sort: {
            type: String,
            default: ''
        },

        /** Pola przekazywane do każdego komponentu komórki. */
        cellProps: {
            type: Object,
            default: () => ({})
        },

        rowDisabled: {
            type: Function,
            default: () => false
        },

        rowKey: {
            type: String,
            default: 'id'
        },

        /** Nagłówek kolumny ze slotem `actions`. */
        actionsLabel: {
            type: String,
            default: ''
        },

        /** Nagłówek doklejany do góry okna przy przewijaniu strony. */
        stickyHeader: {
            type: Boolean,
            default: false
        }
    },

    computed: {
        columns() {
            return this.listing.visibleColumns;
        },

        hasActionsColumn() {
            return Boolean(this.$scopedSlots.actions);
        },

        pinnedCount() {
            return this.listing.pinnedCount;
        },

        fields() {
            const fields = this.columns.map((column, index) => ({
                key: column.id,
                label: column.label,
                thClass: column.thClass,
                tdClass: column.tdClass,
                stickyColumn: index < this.pinnedCount,
            }));

            if (this.hasActionsColumn) {
                fields.unshift({
                    key: ACTIONS_KEY,
                    label: '',
                    stickyColumn: true,
                });
            }

            return fields;
        }
    },

    mounted() {
        if (this.stickyHeader) {
            this.initStickyHeader();
        }

        this.applyPinOffsets();
        this.pinResizeListener = () => this.applyPinOffsets();
        window.addEventListener('resize', this.pinResizeListener);
    },

    beforeDestroy() {
        this.destroyStickyHeader();

        if (this.pinResizeListener) {
            window.removeEventListener('resize', this.pinResizeListener);
        }
    },

    watch: {
        sort: {
            immediate: true,
            handler(value) {
                this.headerSort = this.parseSort(value);
            }
        },

        // klon nagłówka trzyma własną kopię <thead>, więc po każdej zmianie
        // zestawu kolumn albo przeładowaniu danych trzeba go zbudować od nowa
        fields() {
            this.applyPinOffsets();
            this.refreshStickyHeader();
        },

        loading(value) {
            if (!value) {
                this.applyPinOffsets();
                this.refreshStickyHeader();
            }
        }
    },

    methods: {
        tableElement() {
            const root = this.$refs.table && this.$refs.table.$el;

            return root && (root.tagName === 'TABLE' ? root : root.querySelector('table'));
        },

        /**
         * bootstrap-vue daje każdej sticky kolumnie `left: 0`, więc przy kilku przypiętych
         * nakładałyby się na siebie. Offsety trzeba policzyć po wyrenderowaniu, bo szerokości
         * kolumn są zależne od treści.
         */
        applyPinOffsets() {
            this.$nextTick(() => {
                const table = this.tableElement();
                if (!table) {
                    return;
                }

                const headCells = table.querySelectorAll(':scope > thead > tr > th');
                // kolumna akcji jest sticky niezależnie od widoku i zawsze stoi pierwsza
                const pinned = (this.hasActionsColumn ? 1 : 0) + this.pinnedCount;
                let offset = 0;

                headCells.forEach((cell, index) => {
                    const cells = [cell, ...table.querySelectorAll(`:scope > tbody > tr > *:nth-child(${index + 1})`)];

                    if (index >= pinned) {
                        cells.forEach(c => c.style.left = '');
                        return;
                    }

                    cells.forEach(c => c.style.left = `${offset}px`);
                    offset += cell.getBoundingClientRect().width;
                });
            });
        },

        initStickyHeader() {
            this.destroyStickyHeader();

            // `destroy()` sprząta tylko klon, o którym wie dana instancja - przy przerwanym
            // cyklu (np. przebudowa kolumn w trakcie klonowania) w kontenerze zostałby sierota
            if (this.$refs.tableClone) {
                this.$refs.tableClone.innerHTML = '';
            }

            const tableElement = this.tableElement();

            if (tableElement && this.$refs.tableClone) {
                this.stickyTable = new StickyTableHeader(tableElement, this.$refs.tableClone, { max: 0 });
            }
        },

        destroyStickyHeader() {
            if (this.stickyTable) {
                this.stickyTable.destroy();
                this.stickyTable = null;
            }
        },

        refreshStickyHeader() {
            if (!this.stickyHeader) {
                return;
            }

            this.$nextTick(() => this.initStickyHeader());
        },

        headSlot(column) {
            return `head(${column.id})`;
        },

        cellSlot(column) {
            return `cell(${column.id})`;
        },

        parseSort(value) {
            if (!value) {
                return { sortKey: '', order: '' };
            }

            return {
                sortKey: value.replace(/_.+$/, ''),
                order: value.replace(/^.+_/, '').toUpperCase(),
            };
        },

        sortBy(sortKey) {
            if (!sortKey) {
                return;
            }

            const order = (this.headerSort.sortKey === sortKey && this.headerSort.order === 'ASC') ? 'DESC' : 'ASC';
            this.headerSort = { sortKey, order };
            this.$emit('sortChanged', `${sortKey}_${order.toLowerCase()}`);
        },

        rowClass(item) {
            return (item && this.rowDisabled(item)) ? 'is-disabled' : null;
        },

        cellBindings(column, item) {
            return {
                ...this.cellProps,
                record: item,
                column,
                disabled: this.rowDisabled(item),
            };
        }
    },

    data() {
        return {
            headerSort: {
                sortKey: '',
                order: ''
            },
            stickyTable: null,
            pinResizeListener: null,
        }
    },
}
</script>

<template>
    <div class="listing-table">
    <b-table
        ref="table"
        :items="items"
        :fields="fields"
        :busy="loading"
        :primary-key="rowKey"
        :tbody-tr-class="rowClass"
        :empty-text="$t('noData')"
        responsive
        no-border-collapse
        show-empty
        small
        hover
    >
        <template v-if="hasActionsColumn" #head(__actions)>
            <div class="header-item">{{ actionsLabel }}</div>
        </template>

        <template v-if="hasActionsColumn" #cell(__actions)="{ item }">
            <slot name="actions" :item="item" :disabled="rowDisabled(item)" />
        </template>

        <template v-for="column in columns" v-slot:[headSlot(column)]>
            <div :key="column.id">
                <div
                    v-for="(headerItem, index) in column.headerItems"
                    :key="index"
                    :class="[
                        'header-item',
                        headerItem.sortKey && 'header-item--sortable',
                        headerItem.sortKey && headerItem.sortKey === headerSort.sortKey && 'header-item--selected',
                    ]"
                    @click="sortBy(headerItem.sortKey)"
                >
                    {{ headerItem.label }}
                    <i
                        v-if="headerItem.sortKey && headerItem.sortKey === headerSort.sortKey"
                        :class="headerSort.order === 'DESC' ? 'fa fa-arrow-down sort-direction' : 'fa fa-arrow-up sort-direction'"
                    />
                </div>
            </div>
        </template>

        <template v-for="column in columns" v-slot:[cellSlot(column)]="{ item }">
            <component
                v-if="column.displayComponent"
                :key="column.id"
                :is="column.displayComponent"
                v-bind="cellBindings(column, item)"
                v-on="$listeners"
            />
            <span v-else :key="column.id">{{ column.format(item) }}</span>
        </template>

        <template #table-busy>
            <div class="text-center my-4">
                <i class="fa fa-spinner fa-spin fa-2x" />
            </div>
        </template>
    </b-table>

    <div v-if="stickyHeader" class="table-responsive table-clone">
        <table ref="tableClone" class="table b-table table-sm b-table-no-border-collapse" />
    </div>
    </div>
</template>

<style scoped lang="scss">

.listing-table {
    position: relative;
    font-size: 0.8rem;

    // Podświetlenie wiersza. Nakładka zamiast gotowego koloru, bo komórki mają różne tła
    // (biel, #fbfbfb w działach) i każde ma zostać przyciemnione tak samo. Alfa dobrana
    // tak, by biała komórka wypadła na #f8f8f8.
    --listing-hover-overlay: rgba(0, 0, 0, 0.0275);

    .table-clone {
        // biblioteka nadaje kontenerowi tylko `position: fixed`, bez z-indeksu, więc
        // sticky komórki tabeli (kolumna akcji: 2, nagłówek: 5) malowałyby się nad klonem
        z-index: 6;
        background-color: var(--colorWhite);
        box-shadow: 0 4px 6px -2px rgba(var(--colorGrayRgb), 0.3);
    }

    // `small` na b-table ścina padding do .3rem, co spłaszcza nagłówek względem
    // pierwotnej tabeli; komórki danych zostają wąskie, wraca tylko wysokość nagłówka
    ::v-deep th {
        padding: 0.75rem;
        border-bottom: 1px solid #e3e6f0;
        vertical-align: middle;
    }

    // bootstrap-vue nadaje komórkom nagłówka `.table-b-table-default` z color: #212529,
    // przez selektor `.table.b-table > thead > tr > .table-b-table-default` - trzeba go przebić
    ::v-deep table.table.b-table > thead > tr > th {
        color: #666;
        // tabela stoi tuż pod zakładkami, bootstrapowe `.table th { border-top }`
        // dokładało drugą kreskę zaraz pod aktywnym tabem
        border-top: 0;
    }

    // `.table-hover` przy najechaniu podmienia też kolor tekstu na #212529 - zostawiamy
    // tylko zmianę tła, żeby wiersz nie zmieniał kontrastu pod kursorem
    ::v-deep table.table.b-table.table-hover > tbody > tr:hover {
        color: inherit;
        background-color: var(--listing-hover-overlay);
    }

    // komórki sticky (akcje i przypięte kolumny) mają własne, nieprzezroczyste tło, więc
    // podświetlenie z <tr> ich nie obejmuje, a bootstrap-vue dodatkowo ciemni w nich tekst.
    // Zamiast dobierać kolor ręcznie nakładamy tę samą warstwę, której bootstrap używa na
    // wierszu - dzięki temu wynik jest identyczny niezależnie od tła komórki
    ::v-deep table.table.b-table.table-hover > tbody > tr:hover > .table-b-table-default {
        color: inherit;
        background-image: linear-gradient(var(--listing-hover-overlay), var(--listing-hover-overlay));
    }

    // sticky kolumna dostaje od bootstrap-vue stały z-index, więc menu rozwinięte
    // w jednym wierszu chowa się pod komórkami wierszy poniżej
    ::v-deep td.b-table-sticky-column:has(.dropdown.show) {
        z-index: 4;
    }

    // Drawer ze slotu akcji renderuje się wewnątrz sticky komórki, a ta tworzy własny
    // kontekst układania - bez podniesienia jej z-indeksu backdrop drawera nie przykrywa
    // niczego, co ma wyżej w korzeniu: sticky nagłówka, sąsiednich komórek ani paginacji.
    // Otwarty drawer poznajemy po braku `display: none` na backdropie (bootstrap-vue trzyma
    // go zamontowanego dla każdego wiersza), a długość selektora jest podyktowana regułą
    // `.table.b-table > tbody > tr > .b-table-sticky-column`, którą trzeba przebić.
    ::v-deep table.table.b-table > tbody > tr > td.b-table-sticky-column:has(.b-sidebar-backdrop:not([style*="display: none"])) {
        z-index: 1035;
    }

    ::v-deep tr.is-disabled {
        opacity: 0.5;
    }

    .header-item {
        position: relative;
        font-size: 0.85rem;
        white-space: nowrap;

        &--sortable {
            cursor: pointer;
        }

        &--selected {
            color: #4E73DF;

            i.sort-direction {
                position: absolute;
                right: 0;
                top: 50%;
                transform: translateY(-50%);
            }
        }
    }
}

</style>
