<template>
    <div>
        <div class="loading text-center" v-if="loading">
            <i class="fa fa-spinner fa-pulse fa-2x fa-fw"></i>
        </div>

            <table class="table mb-5" v-if="loading === false">

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Data</th>
                        <th>Klient</th>
                        <th>Produkt</th>
                        <th>Współczynnik</th>

                        <th
                            v-for="dpt in departments"
                            v-text="dpt.name"
                        ></th>
                        <th>Akcje</th>

                    </tr>
                </thead>

                <tbody>

                        <tr v-for="(order, ordersKey) in orders" v-if="order.production.data.length" :key="order.line.id">
                            <td v-text="order.header.orderNumber || order.line.id"></td>
                            <td v-text="order.line.confirmedDate"></td>
                            <td v-text="customerName(order.customer)"></td>
                            <td>
                                {{ order.product.name }}
                                <i class="fa fa-info-circle hasTooltip"
                                   v-if="order.line.description"
                                   @mouseenter="tolltipOwner = order.line.id"
                                   @mouseleave="tolltipOwner = null"
                                >
                                    <div class="mytooltip" v-if="tolltipOwner === order.line.id" v-html="order.line.description.replace(/(?:\r\n|\r|\n)/g, '<br />')">
                                    </div>
                                </i>


                            </td>
                            <td v-text="order.line.factor" class="text-center"></td>

                            <td v-for="(production, prodKey) in order.production.data">
                                <select class="form-control"
                                    v-model="production.status"
                                    @change="updateStatus(production.id, production.status)"
                                    :style="getStatusStyle(production)"
                                >
                                    <option
                                        v-for="status in statuses"
                                        :value="status.value"
                                        v-text="status.name"
                                        style="background-color: white"
                                    ></option>
                                </select>
                            </td>

                            <td>

                                <dropdown>
                                    <template>
                                        <a class="dropdown-item" :href="getRouting().get('agreement_line_details') + '/' + order.line.id">
                                            <i class="fa fa-tasks" aria-hidden="true"></i> Panel
                                        </a>

                                        <a class="dropdown-item" href="#"
                                            @click="confirmArchiveModal(order)"
                                        >
                                            <i class="fa fa-archive" aria-hidden="true"></i> Archiwizuj
                                        </a>

                                        <a class="dropdown-item" href="#"
                                           @click="confirmDeleteModal(order)"
                                        >
                                            <i class="fa fa-trash text-danger" aria-hidden="true"></i> <span class="text-danger">Wycofaj z produkcji</span>
                                        </a>

                                    </template>
                                </dropdown>

                            </td>

                        </tr>


                </tbody>

            </table>

            <confirmation-modal
                :show="confirmations.archive.show"
                @answerYes="handleArchive(confirmations.archive.context)"
                @closeModal="confirmations.archive.show = false"
                v-if="confirmations.archive.show"
            >
                <div>
                    <p><strong>Czy archiwizować zlecenie:</strong></p>
                    <ul class="list-unstyled">
                        <li>id: {{ confirmations.archive.context.header.orderNumber}}</li>
                        <li>produkt: {{ confirmations.archive.context.product.name }}</li>
                        <li>klient: {{ customerName(confirmations.archive.context.customer) }}</li>
                    </ul>
                </div>

            </confirmation-modal>

            <confirmation-modal
                :show="confirmations.delete.show"
                @answerYes="handleDelete(confirmations.delete.context)"
                @closeModal="confirmations.delete.show = false"
                v-if="confirmations.delete.show"
            >
                <div>
                    <p><strong>Czy usunąć zlecenie produkcyjne:</strong></p>
                    <ul class="list-unstyled">
                        <li>id: {{ confirmations.delete.context.header.orderNumber}}</li>
                        <li>produkt: {{ confirmations.delete.context.product.name }}</li>
                        <li>klient: {{ customerName(confirmations.delete.context.customer) }}</li>
                    </ul>
                </div>

            </confirmation-modal>


    </div>
</template>

<script>

    import qs from 'qs';
    import moment from 'moment';
    import api from '../../api/neworder';
    import apiProd from '../../api/production';
    import routing from "../../api/routing";
    import productionApi from '../../api/production';
    import Helpers from '../../helpers';
    import Dropdown from '../base/Dropdown';
    import ConfirmationModal from "../base/ConfirmationModal";

    export default {
        name: "ProductionList",

        components: { Dropdown, ConfirmationModal },

        data() {
            return {
                filters: {
                    dateStart: '',
                    dateEnd: '',
                    archived: false,
                    deleted: false,
                    page: 1
                },

                statuses: Helpers.statuses,

                orders: [],
                departments: [],

                tolltipOwner: null,

                loading: false,

                confirmations: {
                    archive: {
                        show: false,
                        busy: false,
                        context: false,
                    },

                    delete: {
                        show: false,
                        busy: false,
                        context: false
                    }
                }
            }
        },

        mounted() {
            this.setFilters();
        },

        watch: {
            filters: {
                handler(value) {
                    let queryString = qs.stringify({
                        dateStart: this.filters.dateStart,
                        dateEnd: this.filters.dateEnd,
                        page: this.filters.page
                    });

                    if (queryString.length > 0) {
                        queryString = '?'.concat(queryString);
                        history.pushState(null, '', window.location.pathname + queryString);
                    }

                    this.fetchData();
                },
                deep: true
            },

        },

        methods: {
            handleDelete(order) {
                this.confirmations.delete.busy = true;
                apiProd.delete(order.line.id)
                    .then(() => {
                        this.orders = this.orders.filter(record => { return record.line.id !== order.line.id; })
                    })
                    .finally(() => {
                        this.confirmations.delete.busy = false;
                        this.confirmations.delete.show = false;
                    });
            },

            handleArchive(order) {
                this.confirmations.archive.busy = true;
                api.archiveAgreement(order.line.id)
                    .then(({data}) => {

                        let idx = this.orders.findIndex((record) => {
                            return record.line.id === order.line.id;
                        });

                        if (idx !== -1) {
                            this.orders.splice(idx, 1);
                        }


                    })
                    .finally(() => {
                        this.confirmations.archive.busy = false;
                        this.confirmations.archive.show = false;
                    })
                ;
            },

            setFilters() {
                let query = qs.parse(window.location.search, { ignoreQueryPrefix: true });
                this.filters.dateStart = query.dateStart;
                this.filters.dateEnd = query.dateEnd;
                this.filters.page = query.page || 1;

                if ((!this.filters.dateStart || !moment(this.filters.dateStart).isValid())) {
                    this.filters.dateStart = moment().startOf('month').format('YYYY-MM-DD');
                }

                if ((!this.filters.dateEnd || !moment(this.filters.dateEnd).isValid())) {
                    this.filters.dateEnd = moment().endOf('month').format('YYYY-MM-DD');
                }
            },

            fetchData() {
                this.loading = true;
                api.fetchAgreements(this.filters)
                    .then(({data}) => {

                        if (data && data.orders) {

                            data.orders.forEach(order => {
                                order.buttonExpanded = false;
                                order.confirmRemove = false;
                            });

                            this.orders = data.orders;
                            this.departments = data.departments;
                        }
                    })
                    .catch(data => {})
                    .finally(() => { this.loading = false; })
            },

            customerName(customer) {
                return Helpers.customerName(customer);
            },

            updateStatus(productionId, newStatus) {
                productionApi.updateStatus(productionId, newStatus);
            },

            getStatusStyle(production) {

                let status = this.statuses.find(item => item.value === production.status);
                if (status) {
                    return 'background-color: '.concat(status.color);
                }

                return '';
            },

            confirmArchiveModal(item) {
                this.confirmations.archive.context = item;
                this.confirmations.archive.show = true;
            },

            confirmDeleteModal(item) {
                this.confirmations.delete.context = item;
                this.confirmations.delete.show = true;
            },

            getRouting() {
                return routing;
            }
        }
    }

</script>

<style scoped lang="scss">

    .hasTooltip {
        position: relative;
        z-index: 10;

        .mytooltip {
            font-family: 'Roboto', sans-serif;
            position: absolute;
            top: calc(10% + 15px);
            left: calc(100% + 15px);
            width: 200px;
            background-color: lightyellow;
            border: 1px solid #ccc;
            border-radius: 2px;

            font-size: 0.875rem;
            line-height: 1.175rem;
            padding: 10px;
            opacity: 0.9;

            z-index: 20;
        }
    }


</style>