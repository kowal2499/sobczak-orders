<template>
    <div>
        <SectionBlockTitle block :title="$t('products.title')" :breadcrumbs="breadcrumbs">
            <template #filters>
                <ListSearchBar
                    :value="query.q"
                    :placeholder="$t('products.searchPlaceholder')"
                    :search-label="$t('products.search')"
                    :new-label="$t('products.new')"
                    :new-to="{ name: 'product-new' }"
                    @search="onSearch"
                />
            </template>
        </SectionBlockTitle>

        <SectionBlock class="section-gap">
            <b-overlay :show="loading" rounded="sm">
                <div v-if="items.length" class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th style="width: 30%">{{ $t('products.field.name') }}</th>
                                <th>{{ $t('products.field.description') }}</th>
                                <th>{{ $t('products.field.factor') }}</th>
                                <th style="width: 150px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="product in items" :key="product.id">
                                <td>{{ product.name }}</td>
                                <td>{{ product.description }}</td>
                                <td>{{ product.factor }}</td>
                                <td>
                                    <router-link :to="{ name: 'product-edit', params: { id: product.id } }" class="btn btn-light btn-sm">
                                        <i class="fa fa-pencil" aria-hidden="true"></i> {{ $t('products.edit') }}
                                    </router-link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else-if="!loading" class="alert alert-warning mb-0" role="alert">
                    {{ query.q ? $t('products.emptySearch') : $t('products.empty') }}
                </div>
            </b-overlay>

            <div class="d-flex justify-content-end mt-3">
                <b-pagination
                    v-if="total > pageSize"
                    :value="query.page"
                    :total-rows="total"
                    :per-page="pageSize"
                    first-number last-number size="sm" class="mb-0"
                    @input="onPage"
                />
            </div>
        </SectionBlock>
    </div>
</template>

<script>
import ListSearchBar from '@/components/base/ListSearchBar.vue'
import { listQuery, queryWith } from '@/router/listQuery'
import { fetchProducts } from '../repository/productRepository'

export default {
    name: 'ProductList',
    components: { ListSearchBar },
    data: () => ({
        items: [],
        total: 0,
        pageSize: 0,
        loading: false,
    }),
    computed: {
        query() {
            return listQuery(this.$route)
        },
        breadcrumbs() {
            return [
                { icon: 'home', href: '/', label: this.$t('products.breadcrumb.home') },
                { label: this.$t('products.breadcrumb.list') },
            ]
        },
    },
    watch: {
        query: {
            handler: 'fetch',
            immediate: true,
        },
    },
    methods: {
        fetch() {
            this.loading = true
            return fetchProducts(this.query)
                .then(({ data }) => {
                    this.items = data.items
                    this.total = data.total
                    this.pageSize = data.pageSize
                })
                .catch(() => this.$flash.danger(this.$t('products.loadError')))
                .finally(() => this.loading = false)
        },
        onSearch(q) {
            this.$router.push({ query: queryWith(this.$route, { q, page: 1 }) }).catch(() => {})
        },
        onPage(page) {
            this.$router.push({ query: queryWith(this.$route, { page }) }).catch(() => {})
        },
    },
}
</script>
