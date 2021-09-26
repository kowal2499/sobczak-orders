<template>
    <div>

        <vue-select
            :options="selectOptions"
            :multiple="true"
            :filterable="false"
            v-model="selection"
            @search="fetchOptionsWithSearch"
            label="label"
            placeholder="Wyszukaj klienta"
            class="style-chooser"
            v-if="initialized"
        >
            <template slot="option" slot-scope="option">

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

            <div slot="no-options">Nie znaleziono żadnego klienta</div>

        </vue-select>

        <waiting :description="'Trwa pobieranie danych'" v-if="!initialized"></waiting>

    </div>

</template>

<script>

    import VueSelect from 'vue-select'
    import CustomerAPI from '../../api/neworder';
    import Waiting from './Waiting';
    import _ from 'lodash';

    export default {

        name: "CustomerSelect",

        components: { VueSelect, Waiting },

        props: {
            value: {
                default: () => []
            }
        },

        data() {
            return {
                selectOptions: [],
                selection: this.value,
                q: '',

                /**
                 * Wymagane by nie tworzyć komponentu z pustymi opcjami. Gdy nie ma jeszcze opcji to nieprawidłowo ustawiają się wartości początkowe (selection)
                 */
                initialized: false
            }
        },

        mounted() {
            this.fetch();
            this.selection = this.selection.map(this.prepareOption);
        },

        watch: {
            selection(val) {
                this.$emit('input', val.map(v => v.id));
            }
        },

        methods: {

            fetch() {

                return new Promise(((resolve, reject) => {

                    CustomerAPI.findCustomers(this.q)
                        .then(({data}) => {
                            if (Array.isArray(data)) {
                                this.selectOptions = data
                                    .map(this.prepareOption)
                                    .filter(option => {
                                        return this.value.indexOf(option.id) === -1;
                                    })
                                ;
                            } else {
                                this.selectOptions = [];
                            }
                            this.initialized = true;
                            resolve();
                        })
                        .catch(() => {
                            reject();
                        })

                }));
            },

            fetchOptionsWithSearch: _.debounce(function(search, loading) {
                this.q = search;
                loading(true);
                this.fetch().then(data => {
                    loading(false)
                });
            }, 500),

            prepareOption(customer) {
                let nameText = '';
                if (customer.first_name && customer.last_name) {
                    nameText = ` (${customer.first_name} ${customer.last_name})`;
                }
                customer.label = `${customer.name}${nameText}`;
                return customer;
            }

        }
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