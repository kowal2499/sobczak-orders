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

            <div class="row mt-3">
                <label class="col-2 col-form-label">{{ $t('orders.requestedRealizationDate') }}</label>
                <div class="col">
                    <date-picker v-model="orderDate" :is-range="false"></date-picker>
                </div>
            </div>


            <div class="row mt-3">
                <div class="col text-right">
                    <button class="btn btn-success" :disabled="!canAddProduct()" @click="add"><i class="fa fa-plus" aria-hidden="true"></i> {{ $t('orders.addProduct') }}</button>
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
                                <select class="form-control" v-model="product.productId" @change="product.factor = getProductFactorById(product.productId)">
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
                                <input type="number" min="0" class="form-control" :value="parseInt(product.factor * 100)" @input="factorUpdated(product, $event.target.value)">
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
                                <date-picker v-model="product.requiredDate" :isRange="false"></date-picker>
                            </div>
                        </div>

                        <div class="row mt-3" v-if="product.id">
                            <label class="col-2 col-form-label">{{ $t('orders.attachments') }}</label>
                            <div class="col">

                                <form method="POST" :action="getUploadUrl" enctype="multipart/form-data">
                                    <input type="file" name="sobczak-attach">
                                    <button type="submit" class="btn btn-info"><i class="fa fa-paperclip" aria-hidden="true"></i> Dodaj załącznik do tego produktu</button>
                                </form>
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

    import DatePicker from '../base/datepicker';
    import api from '../../api/neworder';
    import moment from "moment";
    import routing from "../../api/routing";


    export default {
        name: 'Products',
        components: { DatePicker },

        props: {
            products: {
                type: Array,
                default: () => {}
            }
        },

        data() {
            return {
                orderDate: '',
                orderProduct: '',
                orderDescription: '',
                orderFactor: 0,

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

        computed: {
            getUploadUrl() {
                return routing.get('agreement_line_upload');
            }
        },

        mounted() {

            api.fetchProducts()
                .then(({data}) => {
                    if (data) {
                        this.productDefinitions = data.products;
                    }
                })
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