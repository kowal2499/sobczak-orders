<template>
    <div class="wrapper">
        <div class="table-responsive has-dropdown">
            <table class="table" ref="table">
                <thead>
                    <tr>
                        <th v-for="(cell, key) in headers" :key=key :colspan="cell.colspan || ''" :rowspan="cell.rowspan || ''" :class="cell.thClass || ''">
                            <div
                                v-for="(item, key) in cell.items"
                                :key="key"
                                :class="['header-item', item.sortKey && 'header-item--sortable', item.wrapperClass, (item.sortKey === headerSort.sortKey) && 'header-item--selected']"
                                @click="item.sortKey ? sortBy(item.sortKey) : null"
                            >
                                {{ item.name }}
                                <template v-if="item.sortKey === headerSort.sortKey">
                                    <i :class="headerSort.order === 'ASC' && 'fa fa-arrow-up sort-direction'" />
                                    <i :class="headerSort.order === 'DESC' && 'fa fa-arrow-down sort-direction'" />
                                </template>
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <slot />
                </tbody>
            </table>
        </div>

        <div class="table-responsive table-clone has-dropdown">
            <table ref="tableClone" class="table" />
        </div>

        <transition name="fade">
            <div class="courtain text-center" v-if="loading">
                <i class="fa fa-spinner fa-spin fa-2x mt-4"/>
            </div>
        </transition>
    </div>
</template>

<script>
import { StickyTableHeader } from "vh-sticky-table-header";

export default {
    name: "TablePlus",

    props: {
        loading: {
            type: Boolean,
            default: false
        },

        headers: {
            type: Array,
            default: () => ([])
        },

        initialSort: {
            type: String,
            default: ''
        },

        stickyHeader: {
            type: Boolean,
            default: false
        }
    },

    mounted() {
        if (this.initialSort.length > 0) {
            this.headerSort.sortKey = this.initialSort.replace(/_.+$/, '');
            this.headerSort.order = this.initialSort.replace(/^.+_/, '').toUpperCase();
        }

        if (this.stickyHeader) {
            this.initStickyHeader();
        }
    },

    beforeDestroy() {
        if (this.stickyTable) {
            this.stickyTable.destroy();
        }
    },

    watch: {
        loading(newVal, oldVal) {
            if (this.stickyHeader && oldVal === true && newVal === false) {
                this.$nextTick(() => this.initStickyHeader());
            }
        }
    },

    methods: {
        initStickyHeader() {
            if (this.stickyTable) {
                this.stickyTable.destroy();
                this.stickyTable = null;
            }
            if (this.$refs.table && this.$refs.tableClone) {
                this.stickyTable = new StickyTableHeader(
                    this.$refs.table,
                    this.$refs.tableClone,
                    { max: 0 }
                );
            }
        },

        sortBy(sortKey) {
            if (this.headerSort.sortKey === sortKey) {
                this.headerSort.order = (this.headerSort.order === 'ASC') ? 'DESC' : 'ASC';
            } else {
                this.headerSort.sortKey = sortKey;
                this.headerSort.order = 'ASC';
            }
            this.$emit('sortChanged', this.headerSort.sortKey.concat('_', this.headerSort.order.toLowerCase()));
        }
    },

    data() {
        return {
            headerSort: {
                sortKey: '',
                order: ''
            },
            stickyTable: null,
        }
    },
}
</script>

<style scoped lang="scss">

    .wrapper {
        position: relative;

        .table-clone {
            background-color: var(--colorWhite);
            box-shadow: 0 4px 6px -2px rgba(var(--colorGrayRgb), 0.3);
        }

        .courtain {
            position: absolute;
            left: 0;
            right: 0;
            top: 0;
            bottom: 0;
            background-color: rgba(255, 255, 255, 0.9);
            z-index: 1000;
        }
    }

    table.table {
        font-size: 0.8rem;

        th {
            border-bottom: 1px solid #e3e6f0;
            vertical-align: middle;

            .header-item {
                position: relative;
                font-size: 0.85rem;

                a {
                    color: #666;
                }

                a:hover {
                    text-decoration: none !important;
                    color: #4E73DF;
                }

                &--sortable {
                    cursor: pointer;
                }

                &--selected {
                    color: #4E73DF;

                    a {
                        color: #4E73DF;
                    }

                    i.sort-direction {
                        position: absolute;
                        right: 0;
                        top: 50%;
                        transform: translateY(-50%);
                    }
                }

                &.hCenter {
                    text-align: center;
                }
            }

            th.prod {
                min-width: 120px;
                max-width: 120px;
            }
        }

        .fade-enter-active, .fade-leave-active {
            transition: opacity .2s;
        }

        .fade-enter, .fade-leave-to {
            opacity: 0;
        }
    }
</style>