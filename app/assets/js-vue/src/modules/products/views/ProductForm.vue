<template>
    <div>
        <SectionBlockTitle block :title="title" :breadcrumbs="breadcrumbs" />

        <SectionBlock class="section-gap">
            <b-overlay :show="loading" rounded="sm">
                <ValidationObserver ref="form" tag="form" @submit.prevent="onSubmit">
                    <ValidationProvider vid="name" :name="$t('products.field.name')" rules="required" #default="{ errors }">
                        <b-form-group :label="$t('products.field.name')" :invalid-feedback="errors.join(' ')" :state="errors.length ? false : null">
                            <b-form-input v-model="form.name" autocomplete="off" :state="errors.length ? false : null" />
                        </b-form-group>
                    </ValidationProvider>

                    <ValidationProvider vid="factor" :name="$t('products.field.factor')" rules="required|min_value:0" #default="{ errors }">
                        <b-form-group
                            :label="$t('products.field.factor')"
                            :description="$t('products.factorHelp')"
                            :invalid-feedback="errors.join(' ')"
                            :state="errors.length ? false : null"
                        >
                            <b-form-input v-model="form.factorPercent" type="number" min="0" step="any" autocomplete="off" :state="errors.length ? false : null" />
                        </b-form-group>
                    </ValidationProvider>

                    <ValidationProvider vid="description" :name="$t('products.field.description')" #default="{ errors }">
                        <b-form-group :label="$t('products.field.description')" :invalid-feedback="errors.join(' ')" :state="errors.length ? false : null">
                            <b-form-textarea v-model="form.description" rows="3" :state="errors.length ? false : null" />
                        </b-form-group>
                    </ValidationProvider>

                    <router-link :to="{ name: 'products' }" class="btn btn-light mr-3">
                        <i class="fa fa-arrow-left" aria-hidden="true"></i> {{ $t('products.back') }}
                    </router-link>
                    <button type="submit" class="btn btn-primary" :disabled="saving">
                        <font-awesome-icon v-if="saving" icon="spinner" spin class="mr-1" />
                        <i v-else class="fa fa-check-square-o" aria-hidden="true"></i>
                        {{ $t('products.save') }}
                    </button>
                </ValidationObserver>
            </b-overlay>
        </SectionBlock>
    </div>
</template>

<script>
import { applyServerError } from '@/services/serverValidation'
import { createProduct, fetchProduct, updateProduct } from '../repository/productRepository'

// The factor is stored as a fraction but edited as a percentage, as the former Symfony PercentType did.
const toPercent = factor => Math.round(factor * 100 * 10000) / 10000
const toFraction = percent => Math.round(Number(percent) / 100 * 1000000) / 1000000

export default {
    name: 'ProductForm',
    props: {
        productId: { type: Number, default: 0 },
    },
    data: () => ({
        form: { name: '', factorPercent: '', description: '' },
        loading: false,
        saving: false,
    }),
    computed: {
        title() {
            return this.$t(this.productId ? 'products.titleEdit' : 'products.titleNew')
        },
        breadcrumbs() {
            return [
                { icon: 'home', href: '/', label: this.$t('products.breadcrumb.home') },
                { href: '/products', label: this.$t('products.breadcrumb.list') },
                { label: this.title },
            ]
        },
    },
    created() {
        if (this.productId) {
            this.load()
        }
    },
    methods: {
        load() {
            this.loading = true
            return fetchProduct(this.productId)
                .then(({ data }) => {
                    this.form = {
                        name: data.name,
                        factorPercent: toPercent(data.factor),
                        description: data.description || '',
                    }
                })
                .catch(() => this.$flash.danger(this.$t('products.loadError')))
                .finally(() => this.loading = false)
        },
        async onSubmit() {
            if (this.saving || !(await this.$refs.form.validate())) {
                return
            }

            const payload = {
                name: this.form.name,
                factor: toFraction(this.form.factorPercent),
                description: this.form.description,
            }

            this.saving = true
            const request = this.productId ? updateProduct(this.productId, payload) : createProduct(payload)
            return request
                .then(() => {
                    this.$flash.success(this.$t(this.productId ? 'products.saved' : 'products.created'))
                    this.$router.push({ name: 'products' })
                })
                .catch(error => applyServerError(error, this.$refs.form, this.$t('products.saveError')))
                .finally(() => this.saving = false)
        },
    },
}
</script>
