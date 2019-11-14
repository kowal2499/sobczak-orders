<template>
        <ul class="my-pagination" v-if="pages > 1 ">
                <li>
                        <a href="" @click.prevent="switchPage(currentPage-1)" class="page-link" v-if="canShowPrev">Poprzednia</a>
                        <span class="page-link disabled" v-else>Poprzednia</span>
                </li>
                <li v-for="page in visiblePages ">
                        <span class="page-link active" v-if="page == currentPage">{{ page }}</span>
                        <a v-else href="" @click.prevent="switchPage(page)" class="page-link" >{{ page }}</a>
                </li>
                <li>
                        <a href="" @click.prevent="switchPage(currentPage+1)" class="page-link" v-if="canShowNext">Następna</a>
                        <span class="page-link disabled" v-else>Następna</span>
                </li>
        </ul>
</template>

<script>
    export default {
        name: "Pagination",
        props: ['current', 'pages'],
        computed: {
                canShowPrev() {
                        return this.current > 1;
                },
                canShowNext() {
                        return this.current < this.pages;
                },
                visiblePages() {
                        let result = [];

                        return Array.from({length: this.pages}, (v, k) => k+1)
                },
                currentPage() {
                        return parseInt(this.current);
                }
        },
        methods: {
                switchPage(page) {
                        this.$emit('switchPage', page);
                }
        }
    }
</script>

<style scoped lang="scss">
        ul.my-pagination {
                display: flex;
                padding-left: 0;
                list-style: none;
                border-radius: 0.25rem;

                li .page-link {
                        font-size: .85rem;

                        position: relative;
                        display: block;
                        padding: .5rem .75rem;
                        margin-left: -1px;
                        line-height: 1.25;
                        color: #007bff;
                        background-color: #fff;
                        border: 1px solid #dee2e6;

                        &.active {
                                background-color: #4E73DF;
                                border-color: #4E73DF;
                                color: white;
                        }
                        &:hover {
                                z-index: unset;
                        }
                        &.disabled {
                                color: gray;
                                cursor: not-allowed;
                        }
                }
        }
</style>