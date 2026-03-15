<template>
    <div class="agreement-form">
        <!-- Customer Section -->
        <customer-form
            :model-value="form.customerId"
            @update:modelValue="form.customerId = $event"
            class="mb-3"
        />

        <!-- Products Section -->
        <div class="section-container mb-3">
            <div class="section-header">
                <h6 class="section-title">{{ $t('agreement.form.sectionProducts') }}</h6>
                <button
                    class="btn btn-sm btn-success"
                    @click="addProduct"
                    type="button"
                    :disabled="!!agreementId && !$user.can('order.manage:edit')"
                >
                    <i class="fa fa-plus"></i> {{ $t('agreement.form.addProduct') }}
                </button>
            </div>

            <div v-if="form.products.length === 0" class="empty-message">
                {{ $t('agreement.form.emptyProducts') }}
            </div>

            <product-row
                v-for="(product, index) in form.products"
                :key="index"
                :product="product"
                :products="products"
                :disable-remove="form.products.length === 1"
                @update:product="updateProduct(index, $event)"
                @remove="removeProduct(index)"
                class="mb-2"
            />
        </div>

        <!-- Order Details & Attachments Section -->
        <div class="section-container mb-3">
            <h6 class="section-title">{{ $t('agreement.form.sectionDetails') }}</h6>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">{{ $t('agreement.form.orderNumber') }}</label>
                    <input
                        type="text"
                        class="form-control"
                        v-model="form.orderNumber"
                        :placeholder="$t('agreement.form.orderNumberPlaceholder')"
                    />
                    <div v-if="form.orderNumber && !isNumberValid" class="alert alert-danger mt-2 mb-0">
                        <strong>{{ $t('agreement.form.orderNumberUsed') }}</strong>
                    </div>
                </div>
            </div>

            <!-- Attachments -->
            <div class="attachments-subsection">
                <label class="form-label">{{ $t('agreement.form.attachments') }}</label>
                <attachment-form
                    ref="attachmentForm"
                    @vdropzone-queue-complete="onSaveSuccess"
                    @vdropzone-error="onSaveError"
                />
            </div>
        </div>

        <!-- Actions -->
        <div class="action-bar">
            <button
                class="btn btn-primary btn-lg"
                @click="save"
                :disabled="!canSave"
                type="button"
            >
                <i class="fa fa-check-square-o"></i>
                {{ agreementId ? $t('agreement.form.saveEdit') : $t('agreement.form.saveNew') }}
            </button>
        </div>
    </div>
</template>

<script>
import CustomerForm from '../components/CustomerForm.vue';
import AttachmentForm from '../components/AttachmentForm.vue';
import ProductRow from '../components/ProductRow.vue';
import api from '../../../api/neworder';
import routing from "@/api/routing";

function resetForm() {
    return {
        customerId: null,
        orderNumber: '',
        products: [],
        attachments: []
    };
}

export default {
    name: 'AgreementForm',
    components: {
        CustomerForm,
        AttachmentForm,
        ProductRow
    },

    props: {
        agreementId: {
            type: Number,
            default: 0
        }
    },

    data() {
        return {
            form: resetForm(),
            initialOrderNumber: '',
            isNumberValid: false,
            waiting: false,
            products: [],
            isSaving: false,
        };
    },

    created() {
        this.loadProducts();

        if (this.agreementId) {
            this.loadAgreement();
        } else {
            // Dodaj jeden pusty produkt na start
            this.addProduct();
        }

        // Jeśli orderNumber już istnieje (np. załadowany z danych), zwaliduj go
        if (this.form.orderNumber) {
            this.validateNumber();
        }
    },

    watch: {
        'form.customerId'(val) {
            if (val && !this.agreementId) {
                this.getOrderNumber();
            }
        },

        'form.orderNumber'() {
            this.validateNumber();
        }
    },

    computed: {
        canSave() {
            return (
                this.form.customerId !== null &&
                this.form.products.length > 0 &&
                this.form.products.every(p => p.productId !== null) &&
                this.form.products.every(p => p.requiredDate !== null) &&
                this.isNumberValid
            );
        }
    },

    methods: {
        loadProducts() {
            api.fetchProducts()
                .then(({ data }) => {
                    if (data && data.products) {
                        this.products = data.products;
                    }
                })
                .catch((error) => {
                    console.error('Error loading products:', error);
                });
        },
        addProduct() {
            this.form.products.push({
                id: null,
                productId: null,
                factor: 0,
                description: null,
                requiredDate: null,
                isCapacityExceeded: false
            });
        },

        updateProduct(index, updatedProduct) {
            this.form.products = [
                ...this.form.products.slice(0, index),
                updatedProduct,
                ...this.form.products.slice(index + 1)
            ];
        },

        removeProduct(index) {
            if (this.form.products.length > 1) {
                this.form.products.splice(index, 1);
            }
        },

        save() {
            if (!this.canSave) {
                return;
            }

            this.isSaving = true;

            const url = this.agreementId
                ? routing.get('orders_patch') + '/' + this.agreementId
                : routing.get('orders_add');

            const appendFormValues = (formData) => {
                formData.append('customerId', this.form.customerId);
                formData.append('products', JSON.stringify(this.form.products));
                formData.append('orderNumber', this.form.orderNumber);
                if (this.agreementId) {
                    const removedIds = this.$refs.attachmentForm.getRemovedIds();
                    formData.append('removedAttachmentIds', JSON.stringify(removedIds));
                }
            };

            // Jeżeli są załączone pliki, request wysyła dropzone
            if (this.$refs.attachmentForm.getQueuedFiles().length > 0) {
                this.$refs.attachmentForm.processQueue(url, appendFormValues);
            } else {
                const formData = new FormData();
                appendFormValues(formData);

                if (!this.agreementId) {
                    api.storeOrder(formData)
                        .then(() => this.onSaveSuccess())
                        .catch(() => this.onSaveError());
                } else {
                    api.patchOrder(this.agreementId, formData)
                        .then(() => this.onSaveSuccess())
                        .catch(() => this.onSaveError());
                }
            }
        },

        onSaveSuccess() {
            if (!this.isSaving) {
                return;
            }

            this.$flash.success(this.$t('_saveSuccess'));

            if (!this.agreementId) {
                window.location.replace(routing.get('agreements_show'));
            }

            this.isSaving = false;
        },

        onSaveError() {
            this.isSaving = false;
            this.$flash.danger(this.$t('_saveError'));
        },

        loadAgreement() {
            this.waiting = true;
            api.fetchSingleOrder(this.agreementId)
                .then(({data}) => {
                    if (data) {
                        this.form.customerId = data.customerId;
                        this.form.orderNumber = this.initialOrderNumber = data.orderNumber;

                        if (data.products && data.products.length > 0) {
                            this.form.products = data.products.map(p => ({
                                id: p.id,
                                productId: p.productId,
                                factor: p.factor || 1,
                                description: p.description || null,
                                requiredDate: p.requiredDate || null,
                                isCapacityExceeded: p.isCapacityExceeded || false
                            }));
                        } else {
                            this.addProduct();
                        }

                        if (data.attachments && data.attachments.length > 0) {
                            this.$nextTick(() => {
                                this.$refs.attachmentForm.loadExistingFiles(data.attachments);
                            });
                        }
                    }
                })
                .catch((error) => {
                    console.error('Error loading agreement:', error);
                })
                .finally(() => {
                    this.waiting = false;
                });
        },

        validateNumber() {
            if (!this.form.orderNumber) {
                this.isNumberValid = false;
                return;
            }

            api.validateNumber(this.form.orderNumber)
                .then(({data}) => {
                    if (this.agreementId) {
                        this.isNumberValid = (this.form.orderNumber === this.initialOrderNumber) || data.isValid;
                    } else {
                        this.isNumberValid = data.isValid;
                    }
                })
                .catch(() => {
                    this.isNumberValid = false;
                });
        },

        getOrderNumber() {
            if (!this.form.customerId) return;

            api.getNumber(this.form.customerId)
                .then(({data}) => {
                    if (data.next_number) {
                        this.form.orderNumber = data.next_number;
                        this.isNumberValid = true;
                    }
                });
        },

        isLocked(product) {
            return !product.id || (product.id && this.$user.can('order.manage:edit'));
        }
    },
}
</script>

<style lang="scss" scoped>

.agreement-form {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0;
}

.section-container {
    background: var(--colorGrayLight3);
    border-radius: 8px;
    border: 1px solid var(--colorGrayLight80);
    padding: 1.25rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
    transition: box-shadow 0.2s;

    &:hover {
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.section-title {
    margin: 0;
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--colorPrimary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.empty-message {
    color: #6c757d;
    font-size: 0.9rem;
    padding: 1rem;
    text-align: center;
    background: #fff;
    border-radius: 6px;
    border: 1px dashed #dee2e6;
}


.form-label {
    font-weight: 500;
    font-size: 0.85rem;
    margin-bottom: 0.25rem;
    color: #495057;
}

.action-bar {
    display: flex;
    justify-content: flex-end;
    padding: 1rem 0;

    .btn {
        min-width: 200px;
    }
}

// Responsywność
@media (max-width: 768px) {
    .agreement-form {
        padding: 1rem 0;
    }

    .section-container {
        padding: 1rem;
    }

    .action-bar .btn {
        width: 100%;
    }
}
</style>