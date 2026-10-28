<template>
    <div>
        <SectionBlockTitle block :title="title" :breadcrumbs="breadcrumbs" />

        <SectionBlock class="section-gap">
            <b-overlay :show="loading" rounded="sm">
                <ValidationObserver ref="form" tag="form" @submit.prevent="onSubmit">
                    <b-row v-for="(row, i) in rows" :key="i">
                        <b-col v-for="field in row" :key="field.key" :md="field.md">
                            <ValidationProvider :vid="field.key" :name="$t(`customers.field.${field.key}`)" :rules="field.rules || ''" #default="{ errors }">
                                <b-form-group :label="$t(`customers.field.${field.key}`)" :invalid-feedback="errors.join(' ')" :state="errors.length ? false : null">
                                    <b-form-select
                                        v-if="field.key === 'country'"
                                        v-model="form.country"
                                        :options="countryOptions"
                                        :state="errors.length ? false : null"
                                    />
                                    <b-form-input
                                        v-else
                                        v-model="form[field.key]"
                                        :type="field.type || 'text'"
                                        autocomplete="off"
                                        :state="errors.length ? false : null"
                                    />
                                </b-form-group>
                            </ValidationProvider>
                        </b-col>
                    </b-row>

                    <router-link :to="backTo" class="btn btn-light mr-3">
                        <i class="fa fa-arrow-left" aria-hidden="true"></i> {{ $t('customers.back') }}
                    </router-link>
                    <button type="submit" class="btn btn-primary" :disabled="saving">
                        <font-awesome-icon v-if="saving" icon="spinner" spin class="mr-1" />
                        <i v-else class="fa fa-check-square-o" aria-hidden="true"></i>
                        {{ $t('customers.save') }}
                    </button>
                </ValidationObserver>
            </b-overlay>
        </SectionBlock>
    </div>
</template>

<script>
import { applyServerError } from '@/services/serverValidation'
import { createCustomer, fetchCountries, fetchCustomer, updateCustomer } from '../repository/customerRepository'

const FIELDS = ['name', 'street', 'streetNumber', 'apartmentNumber', 'postalCode', 'city', 'country', 'firstName', 'lastName', 'phone', 'email']

const emptyForm = () => Object.fromEntries(FIELDS.map(key => [key, key === 'country' ? null : '']))

export default {
    name: 'CustomerForm',
    props: {
        customerId: { type: Number, default: 0 },
    },
    data: () => ({
        form: emptyForm(),
        countries: [],
        loading: false,
        saving: false,
        rows: [
            [{ key: 'name', rules: 'required' }],
            [{ key: 'street', md: 8 }, { key: 'streetNumber', md: 2 }, { key: 'apartmentNumber', md: 2 }],
            [{ key: 'postalCode', md: 4 }, { key: 'city', md: 4 }, { key: 'country', md: 4 }],
            [{ key: 'firstName' }, { key: 'lastName' }],
            [{ key: 'phone', type: 'tel' }, { key: 'email', type: 'email', rules: 'email' }],
        ],
    }),
    computed: {
        title() {
            return this.$t(this.customerId ? 'customers.titleEdit' : 'customers.titleNew')
        },
        // A customer account may edit its own customer but has no access to the list, as before.
        canList() {
            return this.$user.hasRole('ROLE_CUSTOMERS')
        },
        backTo() {
            return this.canList ? { name: 'customers' } : { name: 'orders' }
        },
        breadcrumbs() {
            return [
                { icon: 'home', href: '/', label: this.$t('customers.breadcrumb.home') },
                this.canList
                    ? { href: '/customers', label: this.$t('customers.breadcrumb.list') }
                    : { label: this.$t('customers.breadcrumb.list') },
                { label: this.title },
            ]
        },
        countryOptions() {
            return [{ value: null, text: '' }, ...this.countries.map(c => ({ value: c.code, text: c.name }))]
        },
    },
    created() {
        this.loading = true
        Promise.all([
            fetchCountries().then(({ data }) => this.countries = data),
            this.customerId ? this.load() : null,
        ])
            .catch(() => this.$flash.danger(this.$t('customers.loadError')))
            .finally(() => this.loading = false)
    },
    methods: {
        load() {
            return fetchCustomer(this.customerId).then(({ data }) => {
                this.form = Object.fromEntries(FIELDS.map(key => [key, data[key] ?? (key === 'country' ? null : '')]))
            })
        },
        async onSubmit() {
            if (this.saving || !(await this.$refs.form.validate())) {
                return
            }

            this.saving = true
            const request = this.customerId ? updateCustomer(this.customerId, this.form) : createCustomer(this.form)
            return request
                .then(() => {
                    this.$flash.success(this.$t(this.customerId ? 'customers.saved' : 'customers.created'))
                    this.$router.push(this.backTo)
                })
                .catch(error => applyServerError(error, this.$refs.form, this.$t('customers.saveError')))
                .finally(() => this.saving = false)
        },
    },
}
</script>
