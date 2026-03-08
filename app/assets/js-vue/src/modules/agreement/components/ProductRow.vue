<template>
    <div class="product-row-item">
        <div class="row align-items-end g-2 mb-2">
            <div class="col-md-6">
                <label class="form-label">{{ $t('agreement.product.label') }}</label>
                <vue-select
                    :options="productOptions"
                    :filterable="true"
                    :reduce="opt => opt.value"
                    v-model="proxyProduct.productId"
                    label="label"
                    :placeholder="$t('agreement.product.selectPlaceholder')"
                    class="style-chooser"
                />
            </div>

            <div class="col-md-3">
                <label class="form-label">
                    {{ $t('agreement.product.factor') }}
                    <font-awesome-icon
                        icon="info-circle"
                        v-b-tooltip.hover :title="$t('orders.factorDesc')"
                    />
                </label>
                <input
                    type="number"
                    class="form-control form-control-sm"
                    @input="handleFactorInput"
                    :value="formatFactorForDisplay(proxyProduct.factor)"
                    :placeholder="$t('agreement.product.factorPlaceholder')"
                    min="1"
                    step="1"
                />
            </div>

            <div class="col-md-2">
                <label class="form-label">{{ $t('agreement.product.realizationDate') }}</label>
                <div class="d-flex align-items-center gap-2">
                    <modal-action
                        v-model="showDatePicker"
                        :title="$t('agreement.product.selectDateTitle')"
                        :configuration="{ size: 'lg' }"
                    >
                        <template #open-action="{ open }">
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-primary"
                                @click="open"
                            >
                                <font-awesome-icon icon="calendar-day" />
                            </button>
                        </template>

                        <template #default>
                            <capacity-aware-day-picker
                                :model-value="tempRealizationDate"
                                :model-capacity-exceeded="proxyProduct.isCapacityExceeded"
                                @update:model-value="tempRealizationDate = $event"
                                @update:model-capacity-exceeded="proxyProduct.isCapacityExceeded = $event"

                                :incoming-factor-value="proxyProduct.factor || 0"
                                :edit-mode="isEdit"
                                :strict-mode="false === $user.can('order.unrestricted_required_date')"
                            />

                            <div class="form-check my-5" v-if="isConfirmationRequired">
                                <input class="form-check-input" type="checkbox" v-model="isCapacityExceededConfirmed" id="defaultCheck1">
                                <label class="form-check-label" for="defaultCheck1">
                                    {{ $t('agreement.product.confirmCapacity') }}<br>
                                    {{ $t('agreement.product.capacityExceededWarning', { date: tempRealizationDate }) }}
                                </label>
                            </div>

                            <div class="mt-3 d-flex justify-content-end gap-2">
                                <button
                                    type="button"
                                    class="btn btn-secondary"
                                    @click="cancelDateSelection"
                                >
                                    {{ $t('agreement.product.cancel') }}
                                </button>
                                <button
                                    type="button"
                                    class="btn btn-primary"
                                    @click="confirmDateSelection"
                                    :disabled="!tempRealizationDate || (isConfirmationRequired && !isCapacityExceededConfirmed)"
                                >
                                    OK
                                </button>
                            </div>
                        </template>
                    </modal-action>

                    <span v-if="proxyProduct.requiredDate" class="text-muted small">
                        {{ getLocalDate(proxyProduct.requiredDate) }}
                    </span>
                    <span v-else class="text-muted small">
                        {{ $t('agreement.product.selectDate') }}
                    </span>
                </div>
            </div>

            <div class="col-md-1">
                <button
                    class="btn btn-danger btn-sm w-100"
                    @click="$emit('remove')"
                    type="button"
                    :disabled="disableRemove"
                    :title="$t('agreement.product.remove')"
                >
                    <i class="fa fa-trash"></i>
                </button>
            </div>
        </div>

        <div class="row g-2">
            <div class="col-12">
                <label class="form-label">{{ $t('agreement.product.description') }}</label>
                <textarea
                    class="form-control form-control-sm"
                    v-model="proxyProduct.description"
                    :placeholder="$t('agreement.product.descriptionPlaceholder')"
                    rows="3"
                ></textarea>
            </div>
        </div>
    </div>
</template>

<script>
import VueSelect from 'vue-select';
import ModalAction from '../../../components/base/ModalAction.vue';
import CapacityAwareDayPicker from '../../schedule/components/CapacityAwareDayPicker.vue';
import { getLocalDate } from '@/helpers'

export default {
    name: 'ProductRow',

    components: {
        VueSelect,
        ModalAction,
        CapacityAwareDayPicker
    },

    props: {
        product: {
            type: Object,
            required: true
        },
        products: {
            type: Array,
            default: () => []
        },
        disableRemove: {
            type: Boolean,
            default: false
        }
    },

    emits: ['update:product', 'remove'],

    data() {
        return {
            proxyProduct: { ...this.product },
            showDatePicker: false,
            tempRealizationDate: null,
            isCapacityExceededConfirmed: false
        };
    },

    computed: {
        productOptions() {
            return this.products.map(product => ({
                value: product.id,
                label: product.name
            }));
        },

        isEdit() {
            return this.product.id !== null
        },

        isConfirmationRequired() {
            return this.$user.can('order.unrestricted_required_date') && this.proxyProduct.isCapacityExceeded && this.proxyProduct.tempRealizationDate !== null;
        }
    },

    methods: {
        confirmDateSelection() {
            if (this.tempRealizationDate) {
                this.proxyProduct.requiredDate = this.tempRealizationDate;
            }
            this.showDatePicker = false;
        },

        cancelDateSelection() {
            this.tempRealizationDate = null;
            this.showDatePicker = false;
        },

        formatFactorForDisplay(factor) {
            const displayValue = factor * 100;
            const rounded = Math.round(displayValue * 100) / 100;
            if (Number.isInteger(rounded)) {
                return rounded.toString();
            }
            return rounded.toFixed(2);
        },

        handleFactorInput(e) {
            const inputValue = parseFloat(e.target.value);
            if (!isNaN(inputValue)) {
                this.proxyProduct.factor = inputValue / 100;
            }
        },

        getLocalDate: getLocalDate
    },


    watch: {
        showDatePicker(isOpen) {
            if (isOpen) {
                // Inicjalizuj temp date przy otwieraniu modala
                this.tempRealizationDate = this.proxyProduct.requiredDate;
            }
        },

        'proxyProduct.productId'(newProductId, oldProductId) {
            if (newProductId && newProductId !== oldProductId) {
                const selectedProduct = this.products.find(p => p.id === newProductId);
                if (selectedProduct) {
                    this.proxyProduct.factor = selectedProduct.factor || 0;
                    this.proxyProduct.description = selectedProduct.description || '';
                }
            }
        },

        'proxyProduct.factor'(newFactor, oldFactor) {
            if (this.proxyProduct.requiredDate && newFactor > oldFactor) {
                this.proxyProduct.requiredDate = null
                this.$flash.info(this.$t('agreement.product.factorIncreasedInfo'))
            }
        },

        proxyProduct: {
            handler(newVal) {
                if (JSON.stringify(this.product) !== JSON.stringify(newVal)) {
                    this.$emit('update:product', newVal);
                }
            },
            deep: true
        },

        product: {
            handler(newVal) {
                this.proxyProduct = { ...newVal };
            },
            deep: true
        }
    }
};
</script>

<style lang="scss" scoped>
.product-row-item {
    background-color: #ffffff;
    padding: 0.875rem;
    border-radius: 6px;
    border: 1px solid #e9ecef;
    transition: all 0.2s;

    &:hover {
        border-color: #ced4da;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }
}

.form-label {
    font-weight: 500;
    font-size: 0.85rem;
    margin-bottom: 0.25rem;
    color: #495057;
}

.gap-2 {
    gap: 0.5rem;
}
</style>
