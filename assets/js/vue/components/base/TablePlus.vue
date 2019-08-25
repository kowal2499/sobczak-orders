<template>
    <table class="table">

        <thead>
            <tr v-for="header in headers">

                <th v-for="cell in header" :colspan="cell.colspan || ''" :rowspan="cell.rowspan || ''">
                    <div class="wrapper">
                        <a href="#" v-if="cell.sortKey" @click.prevent="sortBy(cell)" :class="{ selected: cell.sortKey === headerSort.sortKey }">
                            {{ cell.name }}
                        </a>
                        <span v-else>{{ cell.name }}</span>

                        <span v-if="cell.sortKey === headerSort.sortKey" class="iconWrap">
                            <i class="fa fa-arrow-down" aria-hidden="true" v-if="headerSort.order === 'DESC'"></i>
                            <i class="fa fa-arrow-up" aria-hidden="true" v-if="headerSort.order === 'ASC'"></i>
                        </span>
                    </div>
                </th>

            </tr>
        </thead>

        <tbody>
            <slot></slot>
        </tbody>

    </table>
</template>

<script>
    export default {
        name: "TablePlus",

        props: {
            loading: {
                type: Boolean,
                default: false
            },

            headers: {
                type: Array,
                default: []
            },

            initialSort: {
                type: String,
                default: ''
            }
        },

        data() {
            return {
                headerSort: {
                    sortKey: this.initialSort,
                    order: 'ASC'
                }
            }
        },

        methods: {
            sortBy(header) {

                if (this.headerSort.sortKey === header.sortKey) {
                    this.headerSort.order = (this.headerSort.order === 'ASC') ? 'DESC' : 'ASC';
                } else {
                    this.headerSort.sortKey = header.sortKey;
                    this.headerSort.order = 'ASC';
                }

                this.$emit('sortChanged', this.headerSort.sortKey.concat(':', this.headerSort.order));
            }
        }
    }
</script>

<style scoped lang="scss">
    table.table {
        font-size: 0.8rem;

        th {
            vertical-align: middle;
            text-align: center;
            .wrapper {
                font-size: 0.85rem;
                display: flex;
                align-items: center;
                justify-content: center;

                a {
                    color: #666;
                }

                a:hover {
                    text-decoration: none !important;
                    color: #4E73DF;
                }

                .selected {
                    color: #4E73DF;
                }

                .iconWrap {
                    i {
                        padding-left: 3px;
                        color: #4E73DF;
                    }
                }
            }
        }
    }
</style>