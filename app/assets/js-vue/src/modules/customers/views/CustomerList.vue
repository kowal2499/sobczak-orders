<template>
    <div>
        <SectionBlockTitle block :title="$t('customers.title')" :breadcrumbs="breadcrumbs">
            <template #actions>
                <ListSearchBar
                    :value="query.q"
                    :placeholder="$t('customers.searchPlaceholder')"
                    :search-label="$t('customers.search')"
                    :new-label="$t('customers.new')"
                    :new-to="{ name: 'customer-new' }"
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
                                <th>{{ $t('customers.column.name') }}</th>
                                <th>{{ $t('customers.column.contact') }}</th>
                                <th>{{ $t('customers.column.address') }}</th>
                                <th>{{ $t('customers.column.postalCode') }}</th>
                                <th>{{ $t('customers.column.city') }}</th>
                                <th>{{ $t('customers.column.country') }}</th>
                                <th style="width: 150px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="customer in items" :key="customer.id">
                                <td class="align-middle">
                                    <div>{{ customer.name }}</div>
                                    <small v-if="customer.firstName || customer.lastName" class="text-muted">
                                        {{ customer.firstName }} {{ customer.lastName }}
                                    </small>
                                </td>
                                <td class="align-middle">
                                    <div v-if="customer.phone">
                                        <i class="fa fa-phone-square contact-icon" aria-hidden="true"></i> {{ customer.phone }}
                                    </div>
                                    <div v-if="customer.email">
                                        <i class="fa fa-paper-plane contact-icon" aria-hidden="true"></i>
                                        <a :href="`mailto:${customer.email}`" style="word-break: break-all">{{ customer.email }}</a>
                                    </div>
                                </td>
                                <td class="align-middle">{{ [customer.street, customer.streetNumber, customer.apartmentNumber].filter(Boolean).join(' ') }}</td>
                                <td class="align-middle text-nowrap">{{ customer.postalCode }}</td>
                                <td class="align-middle">{{ customer.city }}</td>
                                <td class="align-middle">{{ customer.country }}</td>
                                <td class="align-middle text-right">
                                    <router-link :to="{ name: 'customer-edit', params: { id: customer.id } }" class="btn btn-outline-primary btn-sm text-nowrap">
                                        <i class="fa fa-pencil" aria-hidden="true"></i> {{ $t('customers.edit') }}
                                    </router-link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else-if="!loading" class="text-muted py-3 text-center">
                    {{ query.q ? $t('customers.emptySearch') : $t('customers.empty') }}
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
import { fetchCustomers } from '../repository/customerRepository'

export default {
    name: 'CustomerList',
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
                { icon: 'home', href: '/', label: this.$t('customers.breadcrumb.home') },
                { label: this.$t('customers.breadcrumb.list') },
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
            return fetchCustomers(this.query)
                .then(({ data }) => {
                    this.items = data.items
                    this.total = data.total
                    this.pageSize = data.pageSize
                })
                .catch(() => this.$flash.danger(this.$t('customers.loadError')))
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

<style scoped>
.contact-icon {
    width: 1rem;
    color: var(--colorSecondary);
}
</style>
