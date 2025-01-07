<template>
    <div>
        <waiting description="Trwa pobieranie danych" class="mb-3" v-if="isBusy" />

        <vue-select
            :options="filteredOptions"
            :multiple="true"
            :filterable="false"
            v-model="selection"
            @search="fetchOptionsWithSearch"
            label="label"
            placeholder="Wyszukaj klienta"
            class="style-chooser"
        >
            <template v-slot:option="option">
                <div class="card-body d-flex p-1">
                    <div style="flex: 4">
                        <strong>{{ option.name }}</strong> {{ option.first_name }} {{ option.last_name }}
                        <div v-if="option.phone" class="text-secondary"><small>{{ option.phone }}</small></div>
                        <div v-if="option.email" class="text-secondary"><small>{{ option.email }}</small></div>
                    </div>
                    <div style="flex: 1">
                        {{ option.city }}
                    </div>
                    <div style="flex: 1">
                        {{ option.country }}
                    </div>
                </div>
            </template>

            <template v-slot:no-options>
                Nie znaleziono żadnego klienta
            </template>
        </vue-select>
    </div>
</template>

<script>
    import VueSelect from 'vue-select'
    import CustomerAPI from '../../api/neworder'
    import Waiting from './Waiting'
    import _ from 'lodash'

    export default {
        name: 'CustomerSelect',

        components: { VueSelect, Waiting },

        props: {
            value: {
                type: Array,
                default: () => []
            }
        },

        mounted() {
            this.fetchOptions()
        },

        computed: {
            selectedIds() {
                return this.value.map(v => v.customer)
            },

            selection: {
                get() {
                    return this.options.filter(opt => this.selectedIds.includes(opt.id))
                },
                set(value) {
                    this.$emit('input', value.map(v => ({ customer: v.id })))
                }
            },

            filteredOptions() {
                return this.options.filter(opt => !this.selectedIds.includes(opt.id))
            }
        },

        methods: {
            fetchOptions() {
                this.isBusy = true
                return CustomerAPI.findCustomers(this.q)
                    .then(({data}) => {
                        this.options = Array.isArray(data) ? data.map(this.prepareOption) : [];
                    })
                    .finally(() => this.isBusy = false)
            },

            fetchOptionsWithSearch: _.debounce(function(search, loading) {
                this.q = search;
                loading(true);
                this.fetchOptions().then(() => loading(false))
            }, 500),

            prepareOption(customer) {
                let nameText = '';
                if (customer.first_name && customer.last_name) {
                    nameText = ` (${customer.first_name} ${customer.last_name})`;
                }
                customer.label = `${customer.name}${nameText}`;
                return customer;
            }
        },

        data: () => ({
            isBusy: false,
            options: [],
            q: '',
        })
    }
</script>

<style lang="scss" >
    .style-chooser {
        .vs__search::placeholder {
            color: gray;
        }
        .vs__dropdown-option--highlight {
            background: #4E73DF;
            color: #fff !important;
        }
        .vs__dropdown-option--highlight .text-secondary {
            color: #d5d5d5 !important;
        }
    }
</style>