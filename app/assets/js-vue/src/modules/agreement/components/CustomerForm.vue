<template>
    <div class="section-container">
        <h6 class="section-title">{{ $t('agreement.customer.sectionTitle') }}</h6>

        <!-- Search Input -->
        <div class="mb-3" v-if="!selectedCustomer.id">
            <div class="input-group">
                <div class="input-group-prepend">
                    <div class="input-group-text text-primary background-color-white-100">
                        <font-awesome-icon icon="search" />
                    </div>

                </div>
                <input
                    type="text"
                    class="form-control"
                    :placeholder="$t('agreement.customer.searchPlaceholder')"
                    v-model="customerSearch"
                />
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="fetchingCustomers" class="loading-message">
            <font-awesome-icon icon="spinner" spin class="mr-2" /> {{ $t('agreement.customer.searching') }}
        </div>

        <!-- Search Results -->
        <div v-if="fetchComplete && !selectedCustomer.id" class="search-results">
            <template v-if="customersFound.length > 0">
                <div class="results-list">
                    <button
                        v-for="(customer, index) in customersFound"
                        :key="customer.id"
                        class="result-item"
                        @click="choose(index)"
                    >
                        <div>
                            <strong>{{ customer.name }}</strong>
                            <br>
                            <small class="text-muted">{{ formatAddress(customer) }}</small>
                        </div>
                        <font-awesome-icon icon="chevron-right" class="text-primary" />
                    </button>
                </div>
            </template>

            <template v-if="customersFound.length === 0 && customerSearch.length > 0">
                <div class="no-results">
                    <font-awesome-icon icon="exclamation-circle" class="mr-2" /> {{ $t('agreement.customer.notFound') }}
                </div>
            </template>
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

export default {
    name: 'CustomerForm',

    props: {
        modelValue: {
            type: Number,
            default: null
        }
    },

    emits: ['update:modelValue'],

    data() {
        return {
            customerSearch: '',
            customersFound: [],
            selectedCustomer: {},
            fetchingCustomers: false,
            fetchComplete: false
        };
    },

    created() {
        this.debouncedSearch = _.debounce(this.searchCustomers, 500);

        // Jeśli mamy już wybranego klienta, załaduj jego dane
        if (this.modelValue) {
            this.loadCustomer(this.modelValue);
        }
    },

    methods: {
        searchCustomers() {
            if (this.customerSearch.length < 2) {
                this.customersFound = [];
                this.fetchComplete = false;
                return;
            }

            this.fetchingCustomers = true;
            this.fetchComplete = false;

            api.findCustomers(this.customerSearch)
                .then(({data}) => {
                    this.customersFound = Array.isArray(data) ? data : [];
                })
                .catch((error) => {
                    console.error('Error searching customers:', error);
                    this.customersFound = [];
                })
                .finally(() => {
                    this.fetchingCustomers = false;
                    this.fetchComplete = true;
                });
        },

        choose(index) {
            this.selectedCustomer = { ...this.customersFound[index] };
            this.$emit('update:modelValue', this.selectedCustomer.id);
            this.customersFound = [];
            this.customerSearch = '';
            this.fetchComplete = false;
        },

        clearSelection() {
            this.selectedCustomer = {};
            this.$emit('update:modelValue', null);
            this.customerSearch = '';
        },

        loadCustomer(customerId) {
            api.fetchCustomer(customerId)
                .then(({data}) => {
                    if (data) {
                        this.selectedCustomer = data;
                    }
                })
                .catch((error) => {
                    console.error('Error loading customer:', error);
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
        customerSearch() {
            this.debouncedSearch();
        },

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

<style lang="scss" scoped>

.section-title {
    margin: 0 0 1rem 0;
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--colorPrimary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.loading-message {
    color: #0c5460;
    background: #d1ecf1;
    border-radius: 6px;
    padding: 0.75rem 1rem;
    font-size: 0.9rem;
}

.search-results {
    max-height: 400px;
    overflow-y: auto;
}

.results-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.result-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.875rem 1rem;
    background: var(--colorWhite100);
    border: 1px solid var(--colorGrayLight80);
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
    text-align: left;
    width: 100%;

    &:hover {
        border-color: var(--colorGrayLight40);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }

    strong {
        color: var(--colorText);
    }

    small {
        font-size: 0.85rem;
    }
}

.no-results {
    color: #856404;
    background: #fff3cd;
    border-radius: 6px;
    padding: 0.75rem 1rem;
    text-align: center;
    font-size: 0.9rem;
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
