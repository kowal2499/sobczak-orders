<template>
    <table class="table">

        <thead>
            <tr>
                <th v-for="header in headers" class="text-center">
                    <a href="#" v-if="header.sortKey" @click.prevent="sortBy(header)" :class="{ selected: header.sortKey === headerSort.sortKey }">
                        {{ header.name }}
                    </a>
                    <span v-else>{{ header.name }}</span>

                    <span v-if="header.sortKey === headerSort.sortKey" class="iconWrap">
                        <i class="fa fa-arrow-down" aria-hidden="true" v-if="headerSort.order === 'DESC'"></i>
                        <i class="fa fa-arrow-up" aria-hidden="true" v-if="headerSort.order === 'ASC'"></i>
                    </span>
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
        th {
            position: relative;

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
                position: absolute;
                top: 12px;
                right: -3px;
                i {
                    color: #4E73DF;
                }
            }
        }
    }
</style>