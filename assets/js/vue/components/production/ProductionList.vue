<template>
    <div>

        <filters :model="filters"></filters>

        <table-plus :headers="tableHeaders" :loading="loading" :initialSort="'l.confirmedDate'" @sortChanged="updateSort">

            <tr v-for="(order, ordersKey) in orders" v-if="order.production.data.length" :key="order.line.id">
                <td v-text="order.header.orderNumber || order.line.id"></td>
                <td v-text="order.line.confirmedDate"></td>
                <td v-text="customerName(order.customer)"></td>
                <td>
                    {{ order.product.name }}
                    <i class="fa fa-info-circle hasTooltip"
                       v-if="order.line.description"
                       @mouseenter="tooltipOwner = order.line.id"
                       @mouseleave="tooltipOwner = null"
                    >
                        <div class="mytooltip" v-if="tooltipOwner === order.line.id" v-html="order.line.description.replace(/(?:\r\n|\r|\n)/g, '<br />')">
                        </div>
                    </i>
                </td>
                <td v-text="order.line.factor" class="text-center"></td>

                <td class="production" v-for="(production, prodKey) in order.production.data" v-if="['dpt01', 'dpt02', 'dpt03', 'dpt04', 'dpt05'].indexOf(production.departmentSlug) !== -1">
                    <div class="task">
                        <select class="form-control"
                                v-model="production.status"
                                @change="updateStatus(production.id, production.status)"
                                :style="getStatusStyle(production)"
                        >
                            <option
                                    v-for="status in helpers.statusesPerTaskType(production.departmentSlug)"
                                    :value="status.value"
                                    v-text="status.name"
                                    style="background-color: white"
                            ></option>
                        </select>
                    </div>
                </td>

                <td class="production">

                    <div v-if="getCustomTasks(order.production.data).length">
                        <button href="#" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm mb-3" @click.prevent="order.showCustomTasks = !order.showCustomTasks">
                            <span v-if="order.showCustomTasks === false"><i class="fa fa-eye"></i> <span class="pl-1" >Pokaż</span></span>
                            <span v-if="order.showCustomTasks === true"><i class="fa fa-eye-slash"></i> <span class="pl-1" >Ukryj</span></span>
                        </button>

                        <template v-if="order.showCustomTasks">
                            <div v-for="task in getCustomTasks(order.production.data)">

                                <div class="task">
                                    <label>{{ task.title }}</label>
                                    <select class="form-control"
                                            v-model="task.status"
                                            @change="updateStatus(task.id, task.status)"
                                            style="width: 120px;"
                                            :style="getStatusStyle(task)"
                                    >
                                        <option
                                                v-for="status in helpers.statusesPerTaskType(task.departmentSlug)"
                                                :value="status.value"
                                                v-text="status.name"
                                                style="background-color: white"
                                        ></option>
                                    </select>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div v-else>
                        brak
                    </div>

                </td>

                <td>
                    <dropdown class="icon-only">
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

        </table-plus>

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
    import routing from '../../api/routing';
    import productionApi from '../../api/production';
    import Helpers from '../../helpers';
    import Dropdown from '../base/Dropdown';
    import ConfirmationModal from '../base/ConfirmationModal';
    import Filters from './Filters';
    import TablePlus from '../base/TablePlus';

    export default {
        name: "ProductionList",

        components: { Dropdown, ConfirmationModal, Filters, TablePlus },

        data() {
            return {
                filters: {

                    dateStart: {
                        start: '',
                        end: ''
                    },

                    dateDelivery: {
                        start: '',
                        end: ''
                    },

                    q: '',

                    archived: false,
                    deleted: false,

                    meta: {
                        sort: 'l.confirmedDate:ASC',
                        page: 1,
                    }
                },

                helpers: Helpers,

                orders: [],
                departments: [],

                tooltipOwner: null,

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
                    let query = {
                        dateStart: this.filters.dateStart.start,
                        dateEnd: this.filters.dateStart.end,
                        page: this.filters.meta.page
                    };

                    if (this.filters.dateDelivery.start && String(this.filters.dateDelivery.start).length > 0) {
                        query.dateDeliveryStart = this.filters.dateDelivery.start
                    }

                    if (this.filters.dateDelivery.end && String(this.filters.dateDelivery.end).length > 0) {
                        query.dateDeliveryEnd = this.filters.dateDelivery.end
                    }

                    if (this.filters.q !== '') {
                        query.q = this.filters.q;
                    }

                    let queryString = qs.stringify(query);

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
                this.filters.dateStart.start = query.dateStart;
                this.filters.dateStart.end = query.dateEnd;
                this.filters.dateDelivery.start = query.dateDeliveryStart;
                this.filters.dateDelivery.end = query.dateDeliveryEnd;
                this.filters.q = query.q;
                this.filters.page = query.page || 1;

                if ((!this.filters.dateStart.start || !moment(this.filters.dateStart.start).isValid())) {
                    this.filters.dateStart.start = moment().startOf('month').format('YYYY-MM-DD');
                }

                if ((!this.filters.dateStart.end || !moment(this.filters.dateStart.end).isValid())) {
                    this.filters.dateStart.end = moment().endOf('month').format('YYYY-MM-DD');
                }

                if (moment(this.filters.dateDelivery.start).isValid() === false) {
                    this.filters.dateDelivery.start = '';
                }

                if (moment(this.filters.dateDelivery.end).isValid() === false) {
                    this.filters.dateDelivery.end = '';
                }
            },

            fetchData() {
                this.loading = true;

                let filters = {};
                for(let i of Object.keys(this.filters)) {
                    if (this.filters[i] !== '' && this.filters[i] !== null) {
                        filters[i] = this.filters[i];
                    }
                }

                api.fetchAgreements(filters)
                    .then(({data}) => {

                        if (data && data.orders) {

                            data.orders.forEach(order => {
                                order.buttonExpanded = false;
                                order.confirmRemove = false;
                                order.showCustomTasks = false;
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

                let status = this.helpers.statuses.find(item => item.value === production.status);
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

            updateSort(event) {
                this.filters.meta.sort = event
            },

            getCustomTasks(production) {
                return production.filter(p => { return p.departmentSlug === 'custom_task' ? p : false; })
            },

            getRouting() {
                return routing;
            }
        },

        computed: {
            tableHeaders() {
                let headers = [
                    [
                        { name: 'ID', sortKey: 'a.orderNumber', rowspan: 2 },
                        { name: 'Data', sortKey: 'l.confirmedDate', rowspan: 2 },
                        { name: 'Klient', sortKey: 'c.name', rowspan: 2},
                        { name: 'Produkt', sortKey: 'p.name', rowspan: 2 },
                        { name: 'Wsp.', sortKey: 'l.factor', rowspan: 2 },

                        { name: 'Produkcja', colspan: 5},
                        { name: 'Dodatkowe zadania', rowspan: 2},
                    ],
                    [
                        { name: 'Klejenie'},
                        { name: 'CNC'},
                        { name: 'Szlifowanie'},
                        { name: 'Lakierowanie'},
                        { name: 'Pakowanie'},
                    ]

                ];

                // this.departments.forEach(dpt => { headers.push({ name: dpt.name }); })

                headers[0].push({ name: 'Akcje', rowspan: 2 });
                return headers;
            },

            productionSlugs() {
                return this.departments.map(d => { return d.slug; })
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

    .table tbody td {
        /*vertical-align: middle !important;*/
    }

    .production {

        .task {
            width: 80px;
            label {
                font-size: 0.75rem;
                margin-bottom: 2px;
                color: #aaa;
            }
            select {
                font-size: 0.65rem;
                padding: 5px;
            }
        }

    }


</style>