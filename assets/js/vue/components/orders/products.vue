<template>

    <div class="card">
        <div class="card-header">
            <strong>Produkty</strong>
        </div>
        <div class="card-body">

            <div class="row">
                <label class="col-2 col-form-label">Produkt</label>
                <div class="col">
                    <select class="form-control" v-model="orderProduct">
                        <option value="">- Wybierz -</option>
                        <option :value="product.id"  v-for="product in productDropdown">{{ product.name }}</option>
                    </select>
                </div>
            </div>

            <div class="row mt-3">
                <label class="col-2 col-form-label">Opis wymagań</label>
                <div class="col">
                    <textarea class="form-control" cols="30" rows="3" v-model="orderDescription"></textarea>
                </div>
            </div>

            <div class="row mt-3">
                <label class="col-2 col-form-label">Oczekiwany termin realizacji</label>
                <div class="col">
                    <date-picker v-model="orderDate"></date-picker>
                </div>
            </div>


            <div class="row mt-3">
                <div class="col text-right">
                    <button class="btn btn-success" :disabled="orderDate === '' || orderProduct === ''" @click="add"><i class="fa fa-plus" aria-hidden="true"></i> Dodaj produkt</button>
                </div>
            </div>

            <div class="row mt-3" v-if="allProducts.length > 0">
                <div class="col">
                    <hr>


                    <div v-for="(product, key) in allProducts">

                        <span style="font-size: 16px; font-weight: bold"># {{ key+1 }}</span>

                        <div class="row">
                            <label class="col-2 col-form-label">Produkt</label>
                            <div class="col">
                                <select class="form-control" v-model="allProducts[key].productId">
                                    <option value="">- Wybierz -</option>
                                    <option :value="item.id"  v-for="item in productDropdown">{{ item.name }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <label class="col-2 col-form-label">Opis wymagań</label>
                            <div class="col">
                                <textarea class="form-control" cols="30" rows="3" v-model="allProducts[key].description"></textarea>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <label class="col-2 col-form-label">Oczekiwany termin realizacji</label>
                            <div class="col">
                                <date-picker v-model="allProducts[key].requiredDate"></date-picker>
                            </div>
                        </div>

                        <hr>

                    </div>
                </div>
            </div>

        </div>


    </div>

</template>

<script>

    import DatePicker from '../base/datepicker';
    import api from '../../api/neworder';


    export default {
        name: 'Products',
        components: { DatePicker },
        props: ['value'],

        data() {
            return {

                orderDate: '',
                orderProduct: '',
                orderDescription: '',

                productDropdown: [],

                allProducts: []
            }
        },

        methods: {
            add() {
                this.allProducts.push({
                    productId: this.orderProduct,
                    description: this.orderDescription,
                    requiredDate: this.orderDate
                });

                this.orderProduct = '';
                this.orderDescription = '';
                this.orderDate = '';
            }
        },

        watch: {
            allProducts: {
                handler(val) {
                    this.$emit('input', val);
                },
                deep: true
            }
        },

        mounted() {

            api.fetchProducts()
                .then(({data}) => {
                    if (Array.isArray(data)) {

                        this.productDropdown = [];

                        data.forEach(item => {
                            this.productDropdown.push({
                                id: item.id,
                                name: item.name
                            })
                        });
                    }
                })
        }
    }

</script>

<style scoped>

</style>