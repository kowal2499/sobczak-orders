<template>
    <div class="wrapper">
        <div class="table-responsive has-dropdown">
            <table class="table">
                <thead>
                    <tr>
                        <th v-for="cell in headers" :colspan="cell.colspan || ''" :rowspan="cell.rowspan || ''" :class="cell.thClass || ''">
                            <div
                                v-for="item in cell.items"
                                :class="['header-item', item.sortKey && 'header-item--sortable', item.wrapperClass, (item.sortKey === headerSort.sortKey) && 'header-item--selected']"
                                @click="item.sortKey ? sortBy(item.sortKey) : null"
                            >
                                {{ item.name }}
<!--                                <a href="#" v-if="item.sortKey" @click.prevent="sortBy(item.sortKey)">-->
<!--                                    {{ item.name }}-->
<!--                                </a>-->
<!--                                <span v-else>{{ item.name }}</span>-->

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

        <transition name="fade">
            <div class="courtain text-center" v-if="loading">
                <i class="fa fa-spinner fa-spin fa-2x mt-4"/>
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
            sortBy(sortKey) {
                if (this.headerSort.sortKey === sortKey) {
                    this.headerSort.order = (this.headerSort.order === 'ASC') ? 'DESC' : 'ASC';
                } else {
                    this.headerSort.sortKey = sortKey;
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
            border-bottom: 1px solid #e3e6f0;
            vertical-align: middle;

            .header-item {
                position: relative;
                font-size: 0.85rem;
                padding: 0 1rem;

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