<template>
    <div class="section-container">
        <h6 class="section-title">{{ $t('agreement.customer.sectionTitle') }}</h6>

        <!-- Search Input -->
        <div class="mb-3" v-if="!selectedCustomer.id">
            <vue-select
                :options="customerOptions"
                :filterable="false"
                :loading="fetchingCustomers"
                label="name"
                :placeholder="$t('agreement.customer.searchPlaceholder')"
                @search="debouncedSearch"
                @input="onSelect"
                class="customer-select"
            >
                <template v-slot:option="customer">
                    <div>
                        <strong>{{ customer.name }}</strong>
                        <div v-if="formatAddress(customer)">
                            <small class="text-muted">{{ formatAddress(customer) }}</small>
                        </div>
                    </div>
                </template>
                <template v-slot:no-options>
                    {{ $t('agreement.customer.notFound') }}
                </template>
            </vue-select>
        </div>

        <!-- Selected Customer -->
        <div v-if="selectedCustomer.id" class="selected-customer">
            <div class="customer-info">
                <div class="customer-main">
                    <h6 class="customer-name">
                        <font-awesome-icon icon="user" class="text-success mr-2" />{{ selectedCustomer.name }}
                    </h6>
                    <div class="customer-details">
                        <div v-if="selectedCustomer.first_name || selectedCustomer.last_name" class="detail-row">
                            <small class="detail-label">{{ $t('agreement.customer.contactPerson') }}</small>
                            <span>{{ selectedCustomer.first_name }} {{ selectedCustomer.last_name }}</span>
                        </div>
                        <div v-if="formatAddress(selectedCustomer)" class="detail-row">
                            <small class="detail-label">{{ $t('agreement.customer.address') }}</small>
                            <span>{{ formatAddress(selectedCustomer) }}</span>
                        </div>
                        <div v-if="selectedCustomer.phone || selectedCustomer.email" class="detail-row">
                            <small class="detail-label">{{ $t('agreement.customer.contact') }}</small>
                            <div class="d-flex gap-3">
                                <div v-if="selectedCustomer.phone">
                                    <font-awesome-icon icon="phone" class="mr-2 text-muted" /> {{ selectedCustomer.phone }}
                                </div>
                                <div v-if="selectedCustomer.email">
                                    <font-awesome-icon icon="envelope" class="mr-2 text-muted" /> {{ selectedCustomer.email }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <button
                    class="btn btn-sm btn-secondary"
                    @click="clearSelection"
                    type="button"
                >
                    <font-awesome-icon icon="times" class="mr-2" /> {{ $t('agreement.customer.change') }}
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import api from '../../../api/neworder';
import _ from 'lodash';
import VueSelect from 'vue-select';

export default {
    name: 'CustomerForm',

    components: { VueSelect },

    props: {
        modelValue: {
            type: Number,
            default: null
        }
    },

    emits: ['update:modelValue'],

    data() {
        return {
            customerOptions: [],
            selectedCustomer: {},
            fetchingCustomers: false,
        };
    },

    created() {
        this.debouncedSearch = _.debounce(this.onSearch, 500);
        this.fetchAllCustomers();
        if (this.modelValue) {
            this.loadCustomer(this.modelValue);
        }
    },

    methods: {
        fetchAllCustomers() {
            this.fetchingCustomers = true;
            api.findCustomers('')
                .then(({data}) => {
                    this.customerOptions = Array.isArray(data) ? data : [];
                })
                .finally(() => {
                    this.fetchingCustomers = false;
                });
        },

        onSearch(searchText, loading) {
            loading(true);
            api.findCustomers(searchText)
                .then(({data}) => {
                    this.customerOptions = Array.isArray(data) ? data : [];
                })
                .finally(() => {
                    loading(false);
                });
        },

        onSelect(customer) {
            if (!customer) return;
            this.selectedCustomer = { ...customer };
            this.$emit('update:modelValue', customer.id);
        },

        clearSelection() {
            this.selectedCustomer = {};
            this.$emit('update:modelValue', null);
        },

        loadCustomer(customerId) {
            api.fetchCustomer(customerId)
                .then(({data}) => {
                    if (data) {
                        this.selectedCustomer = data;
                    }
                });
        },

        formatAddress(customer) {
            const parts = [
                customer.street,
                customer.post_code,
                customer.city
            ].filter(Boolean);

            return parts.join(', ');
        }
    },

    watch: {
        modelValue(newVal) {
            if (newVal && !this.selectedCustomer.id) {
                this.loadCustomer(newVal);
            } else if (!newVal) {
                this.selectedCustomer = {};
            }
        }
    }
};
</script>

<style lang="scss">
.customer-select {
    .vs__search::placeholder {
        color: gray;
    }
    .vs__dropdown-option--highlight {
        background: var(--colorPrimary);
        color: #fff !important;

        .text-muted {
            color: #d5d5d5 !important;
        }
    }
}
</style>

<style lang="scss" scoped>

.section-title {
    margin: 0 0 1rem 0;
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--colorPrimary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.selected-customer {
    .customer-info {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
        padding: 1rem;
        background: rgba(var(--colorSuccessRgb), 0.05);
        border-radius: 6px;
        border: 1px solid rgba(var(--colorSuccessRgb), 0.3);
    }

    .customer-main {
        flex: 1;
    }

    .customer-name {
        margin: 0 0 0.75rem 0;
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--colorText);
    }

    .customer-details {
        font-size: 0.9rem;
        color: var(--colorText);
    }

    .detail-row {
        margin-bottom: 0.5rem;

        &:last-child {
            margin-bottom: 0;
        }
    }

    .detail-label {
        display: block;
        font-weight: 500;
        opacity: 0.8;
        margin-bottom: 0.125rem;
    }
}
</style>
