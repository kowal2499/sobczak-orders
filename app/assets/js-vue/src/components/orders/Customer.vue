<template>
    <div class="card">
        <div class="card-header">
            <strong>{{ $t('customer') }}</strong>
        </div>
        <div class="card-body">

            <div class="row">

                <label class="col-2 col-form-label">
                    {{ $t('search') }}
                </label>

                <div class="col">
                    <input type="text" class="form-control" :placeholder="$t('nameAddressContact')" v-model="customerSearch">
                </div>

            </div>

            <div class="row mt-3">
                <div class="col-10 offset-2">

                    <div class="alert alert-info" v-if="fetchingCustomers">
                        <i class="fa fa-spinner fa-pulse fa-fw"></i> {{ $t('searching') }}
                    </div>

                    <template v-if="fetchComplete">
                        <div v-if="customersFound.length > 0">

                            <ul class="list-group">

                                <li class="list-group-item" v-for="(customer, index) in customersFound" style="display: flex; align-items: center; justify-content: space-between">
                                    <div>
                                        <strong>{{ customer.name }}</strong><br>
                                        <small>{{ formatAddress(customer) }}</small>
                                    </div>

                                    <div>
                                        <button class="btn btn-light" @click.prevent="choose(index)"><i class="fa fa-check" aria-hidden="true"></i> {{ $t('choose') }}</button>
                                    </div>
                                </li>

                            </ul>

                        </div>

                        <div v-if="customersFound.length == 0 && !fetchingCustomers">
                            <div class="alert alert-info">
                                <div class="row">
                                    <div class="col">
                                        {{ $t('noResults') }}
                                    </div>

                                    <div class="col text-right" v-if="userCanAddCustomer()">
                                        <a :href="getLink('customers_new')" class="btn btn-success"><i class="fa fa-plus" aria-hidden="true"></i> {{ $t('addNewCustomer') }}</a>
                                    </div>
                                </div>


                            </div>
                        </div>
                    </template>

                </div>
            </div>

            <div class="row mt-3" v-if="selectedCustomer.id">
                <div class="col">
                    <div class="table-responsive">
                        <table class="table" style="width: 100%">
                            <thead>
                            <tr>
                                <th>{{ $t('name') }}</th>
                                <th>{{ $t('address') }}</th>
                                <th>{{ $t('contact') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td>
                                    {{ selectedCustomer.name }}<br>
                                    <small>{{ selectedCustomer.first_name }} {{ selectedCustomer.last_name }}</small>
                                </td>

                                <td>
                                    {{ formatAddress(selectedCustomer) }}
                                </td>

                                <td>
                                    <div v-if="selectedCustomer.phone">
                                        <i class="fa fa-phone-square" aria-hidden="true"></i> {{ selectedCustomer.phone }}
                                    </div>
                                    <div v-if="selectedCustomer.email">
                                        <i class="fa fa-paper-plane" aria-hidden="true"></i> <a
                                            :href="'mailto:'.concat(selectedCustomer.email)">{{ selectedCustomer.email }}</a>
                                    </div>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>

<script>

    import customerApi from '../../api/neworder';
    import routing from '../../api/routing';
    import _ from 'lodash';

    export default {
        name: 'Customer',
        props: ['value'],

        data() {
            return {
                customerSearch: '',
                customersFound: [],
                fetchingCustomers: false,
                fetchComplete: false,

                selectedCustomer: {},
            }
        },

        watch: {
            customerSearch() {
                this.getCandidates();
            },

            value: {
                handler() {
                    if (!this.value) {
                        return;
                    }

                    this.fetchingCustomers = true;

                    customerApi.fetchCustomer(this.value)
                        .then(({data}) => {
                            if (data) {
                                this.selectedCustomer = { ...data }
                            }
                        })
                        .catch()
                        .finally(() => {
                            this.fetchingCustomers = false;
                        })

                },
                immediate: true
            },

            selectedCustomer: {
                handler(val) {
                    this.$emit('input', val.id);
                },
                deep: true
            }
        },

        methods: {
            getCandidates: _.debounce(function() {

                this.customersFound = [];
                if (this.customerSearch == '') {
                    this.fetchComplete = false;
                    return;
                }

                this.fetchingCustomers = true;
                this.fetchComplete = false;

                customerApi.findCustomers(this.customerSearch)
                    .then(({data}) => {
                        if (Array.isArray(data)) {
                            this.customersFound = data
                        } else {
                            this.customersFound = [];
                        }
                    })
                    .finally(() => { this.fetchingCustomers = false; this.fetchComplete = true; })
            }, 500),

            choose(index) {

                this.selectedCustomer = {
                    ... this.customersFound[index]
                };
                this.customerSearch = '';

            },

            formatAddress(customer) {

                let contents = [
                    customer.street,
                    customer.street_number,
                    customer.postal_code,
                    customer.city,
                    customer.country
                ];

                return contents.join(' ');
            },

            getLink(name) {
                return routing.get(name);
            },
            userCanAddCustomer() {
                return this.$user.can(this.$privilages.CAN_CUSTOMERS);
            }
        }
    }
</script>

<style scoped>

</style>