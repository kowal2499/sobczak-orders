<template>
    <div class="card">
        <div class="card-header">
            <strong>{{ $t('products') }}</strong>
        </div>
        <div class="card-body">

            <div class="row">
                <label class="col-2 col-form-label">{{ $t('product') }}</label>
                <div class="col">
                    <select class="form-control" v-model="orderProduct" @change="orderFactor = getProductFactorById(orderProduct) * 100">
                        <option value="">- {{ $t('choose') }} -</option>
                        <option :value="product.id"  v-for="product in productDefinitions.map((i) => { return { id: i.id, name: i.name };})">{{ product.name }}</option>
                    </select>
                </div>
            </div>
            <div class="row mt-3" v-if="userCanProduction()">
                <label class="col-2 col-form-label">
                    {{ $t('orders.factor') }}
                </label>
                <div class="col">
                    <input type="number" min="0" class="form-control" v-model="orderFactor">
                    <small class="form-text text-muted">{{ $t('orders.factorDesc') }}</small>
                </div>
            </div>

            <div class="row mt-3">
                <label class="col-2 col-form-label">{{ $t('orders.requirements') }}</label>
                <div class="col">
                    <textarea class="form-control" cols="30" rows="3" v-model="orderDescription"></textarea>
                </div>
            </div>

            <div class="row mt-3" v-if="isTesting">
                <label class="col-2 col-form-label">{{ $t('orders.requestedRealizationDate') }}</label>
                <div class="col">
                    <CapacityAwareDayPicker
                        v-model="orderDate"
                        :incomingFactorValue="Number(orderFactor)"
                        @capacityExceeded="exc = $event"
                    />
                    <div class="alert alert-danger my-3" v-if="exc > 0">
                        Zdolności produkcyjne są niewystarczające, aby zrealizować zamówienie w wybranym terminie.
                        Można wybrać tę datę, jednak faktyczny termin realizacji zostanie potwierdzony w osobnym komunikacie.
                    </div>
                    <div class="alert alert-info my-3" v-else-if="!orderDate">
                        Wybierz oczekiwany dzień realizacji.
                        <ul>
                            <li>pasek nad numerem dnia wskazuje poziom wykorzystania zdolności produkcyjnych</li>
                            <li>dni w których firma nie pracuje są niemożliwe do wybrania</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="row mt-3" v-else>
                <label class="col-2 col-form-label">{{ $t('orders.requestedRealizationDate') }}</label>
                <div class="col">
                    <date-picker v-model="orderDate" :is-range="false"></date-picker>
                </div>
            </div>


            <div class="row mt-3">
                <div class="col text-right">
                    <button class="btn btn-primary" :disabled="!canAddProduct()" @click="add">
                        <font-awesome-icon icon="plus" class="mr-2" />
                        {{ $t('orders.addProduct') }}
                    </button>
                </div>
            </div>

            <div class="row mt-3" v-if="products.length > 0">
                <div class="col">
                    <hr>

                    <div v-for="(product, key) in products">
                        <span style="font-size: 16px; font-weight: bold"># {{ key+1 }}</span>
                        <div class="row">
                            <label class="col-2 col-form-label">{{ $t('product') }}</label>
                            <div class="col">
                                <select class="form-control" :disabled="isTesting" v-model="product.productId" @change="product.factor = getProductFactorById(product.productId)">
                                    <option value="">- {{ $t('choose') }} -</option>
                                    <option :value="item.id"  v-for="item in productDefinitions.map((i) => { return { id: i.id, name: i.name };})">{{ item.name }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mt-3" v-if="userCanProduction()">
                            <label class="col-2 col-form-label">
                                {{ $t('orders.factor') }}
                            </label>
                            <div class="col">
                                <input type="number" min="0" class="form-control" :disabled="isTesting" :value="parseInt(product.factor * 100)" @input="factorUpdated(product, $event.target.value)">
                                <small class="form-text text-muted">{{ $t('orders.factorDesc') }}</small>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <label class="col-2 col-form-label">{{ $t('orders.requirements') }}</label>
                            <div class="col">
                                <textarea class="form-control" cols="30" rows="3" v-model="product.description"></textarea>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <label class="col-2 col-form-label">{{ $t('orders.requestedRealizationDate') }}</label>
                            <div class="col">
                                <date-picker v-model="product.requiredDate" :isRange="false" :isDisabled="isTesting"></date-picker>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col text-right">
                                <button class="btn btn-danger" @click="remove(key)"><i class="fa fa-trash" aria-hidden="true"></i> {{ $t('orders.removeProduct') }}</button>
                            </div>
                        </div>
                        <hr>
                    </div>
                </div>
            </div>

            <div class="row mt-3" v-else>
                <div class="col">
                    <div class="alert alert-info">
                        {{ $t('orders.emptyProductList') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

</template>

<script>
    import CapacityAwareDayPicker from '@/modules/schedule/components/CapacityAwareDayPicker.vue'
    import DatePicker from '../base/DatePicker';
    import api from '../../api/neworder';
    import moment from "moment";
    import routing from "../../api/routing";

    export default {
        name: 'Products',
        components: {
            DatePicker,
            CapacityAwareDayPicker,
        },

        props: {
            products: {
                type: Array,
                default: () => {}
            }
        },

        computed: {
            getUploadUrl() {
                return routing.get('agreement_line_upload');
            }
        },

        mounted() {
            const urlParams = new URLSearchParams(window.location.search);
            this.isTesting = urlParams.has('test');

            api.fetchProducts()
                .then(({data}) => {
                    if (data) {
                        this.productDefinitions = data.products;
                    }
                })
        },

        data() {
            return {
                orderDate: '',
                orderProduct: '',
                orderDescription: '',
                orderFactor: 0,
                exc: 0,
                isTesting: false,
                productDefinitions: [],
            }
        },

        methods: {
            add() {
                this.products.push({
                    productId: this.orderProduct,
                    description: this.orderDescription,
                    requiredDate: this.orderDate,
                    factor: this.orderFactor / 100
                });

                this.orderProduct = '';
                this.orderDescription = '';
                this.orderDate = '';
                this.orderFactor = 0;

                this.$emit('orderSave');
            },

            remove(index) {
                this.products.splice(index, 1);
            },

            getProductFactorById(productId) {
                let definition = this.productDefinitions.find(i => { return i.id === productId; });

                if (!definition) {
                    return 0;
                }

                return definition.factor;
            },

            factorUpdated(product, val) {
                product.factor = val / 100;
            },

            canAddProduct() {
                return moment(this.orderDate).isValid() && this.orderProduct !== '';
            },

            userCanProduction() {
                return this.$user.can(this.$privilages.CAN_PRODUCTION);
            }
        },



        filters: {
            hundred(arg) {
                return arg * 100;
            }
        }
    }

</script>

<style scoped>

</style>