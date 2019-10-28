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
                        <div class="row">
                            <div class="col-sm-5">
                                <div class="form-group">
                                    <label>{{ $t('orderNumber') }}</label>
                                    <input type="text" class="form-control" v-model="orderNumber">
                                </div>

                                <div class="alert alert-danger" v-if="orderNumber && isNumberValid === false"><strong>{{ $t('orderWasUsed') }}</strong></div>
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

    import Customer from './customer';
    import Products from './products';
    import api from '../../api/neworder';
    import routing from  '../../api/routing';

    export default {
        name: 'NewOrder',
        components: { Customer, Products },

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
                waiting: false
            }
        },

        created() {
            // gdy edytujemy istniejące zamówienie to zacznij od wczytania danych istniejącego zamówienia
            if (this.agreementId > 0) {
                this.waiting = true;

                api.fetchSingleOrder(this.agreementId)
                    .then(({data}) => {
                        if (data) {
                            this.customerId = data.customerId;
                            this.orderNumber = this.initialOrderNumber = data.orderNumber;

                            for (let product of data.products) {
                                this.products.push({ ... product })
                            }
                        }
                    })
                    .catch(() => {})
                    .finally(() => {
                        this.waiting = false;
                    })


            }
        },

        methods: {
            isNotReadyToSave() {
                return !(this.customerId !== null && (Array.isArray(this.products) && this.products.length > 0) && this.isNumberValid);
            },

            save() {
                if (this.isNotReadyToSave()) {
                    return
                }

                if (!this.agreementId) {
                    api.storeOrder(this.customerId, this.products, this.orderNumber)
                        .then((data) => {
                            window.location.replace(routing.get('agreements_show'));
                        })
                        .catch(() => {
                        });
                } else {
                    api.patchOrder(this.agreementId, this.customerId, this.products, this.orderNumber)
                        .then(() => {
                            window.location.replace(routing.get('agreements_show'));
                        })
                        .catch(() => {
                        });
                }
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