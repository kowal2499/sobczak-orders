<template>
    <div v-if="waiting === false">

        <div class="row mt-3">
            <div class="col">
                <customer v-model="customerId"></customer>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col">
                <products :products="products"></products>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col">
                <div class="card">
                    <div class="card-body">

                        <div class="form-row">

                            <div class="form-group col-3">
                                <label>{{ $t('orderNumber') }}</label>
                                <input type="text" class="form-control" v-model="orderNumber">
                                <div class="alert alert-danger" v-if="orderNumber && isNumberValid === false"><strong>{{ $t('orderWasUsed') }}</strong></div>
                            </div>

                            <div class="form-group col-9">
                                Załączniki
                                <vue2dropzone ref="myVueDropzone" id="dropzone"
                                              :options="dropzoneOptions"
                                              v-on:vdropzone-sending="dropzoneBeforeSend"
                                              v-on:vdropzone-success="onSaveSuccess"
                                              v-on:vdropzone-error="onSaveError"
                                />

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-3">
            <button class="btn btn-primary ml-3 mr-3 w-100" :disabled="isNotReadyToSave()" @click="save()">
                <i class="fa fa-check-square-o" aria-hidden="true"></i> <span v-if="!this.agreementId">{{ $t('orders.saveNewOrder') }}</span><span v-else>{{ $t('orders.saveChanges') }}</span></button>
        </div>

    </div>
</template>

<script>

    import Customer from './Customer';
    import Products from './Products';
    import api from '../../api/neworder';
    import routing from  '../../api/routing';
    import Vue2dropzone from 'vue2-dropzone';
    import 'vue2-dropzone/dist/vue2Dropzone.min.css'

    export default {
        name: 'NewOrder',
        components: { Customer, Products, Vue2dropzone },

        props: {
            agreementId: {
                type: Number,
                default: 0
            }
        },

        data() {
            return {
                customerId: null,
                products: [],
                orderNumber: '',
                initialOrderNumber: '',
                isNumberValid: false,
                waiting: false,
                dropzoneOptions: {
                    url: null,
                    thumbnailWidth: 150,
                    maxFilesize: 10,
                    acceptedFiles: 'image/*,application/pdf,.txt,.cs',
                    autoProcessQueue: false,
                    addRemoveLinks: true,
                    uploadMultiple: true,
                }
            }
        },

        created() {

            /**
             * Zacznij od wczytania jego danych jeżeli edytujemy zamówienie
             * W każdym przypadku trzeba ustawić endpoint dla dropzona
             */

            if (this.agreementId > 0) {
                this.dropzoneOptions.url = routing.get('orders_patch') + '/' + this.agreementId;
                this.waiting = true;

                api.fetchSingleOrder(this.agreementId)
                    .then(({data}) => {
                        if (data) {
                            this.customerId = data.customerId;
                            this.orderNumber = this.initialOrderNumber = data.orderNumber;

                            for (let product of data.products) {
                                product.file = '';
                                this.products.push({ ... product })
                            }
                        }
                    })
                    .catch(() => {})
                    .finally(() => {
                        this.waiting = false;
                    })
            } else {
                this.dropzoneOptions.url = routing.get('orders_add')
            }
        },

        methods: {
            isNotReadyToSave() {
                return !(this.customerId !== null && (Array.isArray(this.products) && this.products.length > 0) && this.isNumberValid);
            },

            dropzoneBeforeSend (file, xhr, formData) {
                this.appendFormValues(formData);
            },

            appendFormValues(form) {
                form.append('customerId', this.customerId);
                form.append('products', JSON.stringify(this.products));
                form.append('orderNumber', this.orderNumber);
            },

            save() {
                if (this.isNotReadyToSave()) {
                    return
                }

                /**
                 * Jeżeli są załączone pliki, to request wysyła dropzone
                 */
                if (this.$refs.myVueDropzone.getQueuedFiles().length > 0) {
                    this.$refs.myVueDropzone.processQueue();
                } else {

                    let formData = new FormData();
                    this.appendFormValues(formData);

                    if (!this.agreementId) {
                        api.storeOrder(formData)
                            .then((data) => {
                                this.onSaveSuccess(null, data);
                            })
                            .catch(() => { this.onSaveError(null, null, null); });
                    } else {
                        api.patchOrder(this.agreementId, formData)
                            .then((data) => {
                                this.onSaveSuccess(null, data);
                            })
                            .catch(() => { this.onSaveError(null, null, null); });
                    }
                }

            },

            onSaveSuccess(file, response) {
                if (!this.agreementId) {
                    window.location.replace(routing.get('agreements_show'));
                } else {
                    Event.$emit('message', {
                        type: 'success',
                        content: 'Zapisano zmiany'
                    });
                }
            },

            onSaveError(file, message, xhr) {
                Event.$emit('message', {
                    type: 'error',
                    content: 'Wystąpił błąd'
                });
            },

            getOrderNumber() {
                api.getNumber(this.customerId)
                    .then(({data}) => {
                        if (data.next_number) {
                            this.orderNumber = data.next_number;
                        }
                    })
            },

            validateNumber() {
                api.validateNumber(this.orderNumber)
                    .then(({data}) => {

                        if (this.agreementId > 0) {
                            this.isNumberValid = (this.orderNumber === this.initialOrderNumber) || data.isValid;
                        } else {
                            this.isNumberValid = data.isValid;
                        }

                    })
                    .catch(() => {
                        this.isNumberValid = false;
                    })
            }
        },

        watch: {
            customerId: {
                handler(val) {
                    if (!this.agreementId) {
                        this.getOrderNumber()
                    }
                },
            },

            orderNumber: {
                handler(val) {{
                    this.validateNumber();
                }}
            }
        }
    }

</script>

<style lang="scss" scoped>

</style>