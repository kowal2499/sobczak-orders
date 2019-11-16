<template>
    <div class="wrapper">
        <div class="table-responsive has-dropdown">
            <table class="table">

                <thead>
                    <tr v-for="header in headers">

                        <th v-for="cell in header" :colspan="cell.colspan || ''" :rowspan="cell.rowspan || ''" :class="cell.classHeader || ''">
                            <div class="wrapper" :class="cell.classCell || ''">
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
        </div>

        <transition name="fade">
            <div class="courtain text-center" v-if="loading">
                <i class="fa fa-spinner fa-spin fa-2x mt-4"></i>
            </div>
        </transition>

    </div>
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
                    sortKey: '',
                    order: ''
                }
            }
        },

        mounted() {
            if (this.initialSort.length > 0) {
                this.headerSort.sortKey = this.initialSort.replace(/_.+$/, '');
                this.headerSort.order = this.initialSort.replace(/^.+_/, '').toUpperCase();
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

                this.$emit('sortChanged', this.headerSort.sortKey.concat('_', this.headerSort.order.toLowerCase()));
            }
        }
    }
</script>

<style scoped lang="scss">

    .wrapper {
        position: relative;

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
            vertical-align: middle;
            .wrapper {
                font-size: 0.85rem;

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

                &.hCenter {
                    text-align: center;
                }
            }
        }
    }

    .fade-enter-active, .fade-leave-active {
        transition: opacity .2s;
    }
    .fade-enter, .fade-leave-to {
        opacity: 0;
    }

</style>