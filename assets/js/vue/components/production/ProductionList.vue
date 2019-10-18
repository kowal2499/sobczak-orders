<template>
    <div>

        <filters :model="filters"></filters>

        <table-plus :headers="tableHeaders" :loading="loading" :initialSort="'l.confirmedDate'" @sortChanged="updateSort">

            <tr v-for="(order, ordersKey) in orders" v-if="order.production.data.length" :key="order.line.id">
                <td>
                    {{ order.header.orderNumber || order.line.id }}
                    <div class="badge" :class="getAgreementStatusClass(order.line.status)" v-if="order.line.status !== 10">{{ getAgreementStatusName(order.line.status) }}</div>
                </td>
                <td v-text="order.line.confirmedDate"></td>
                <td v-text="customerName(order.customer)"></td>
                <td>
                    {{ order.product.name }}
                    <tooltip v-if="order.line.description.length > 0">
                        <i slot="visible-content" class="fa fa-info-circle hasTooltip"></i>
                        <div slot="tooltip-content" class="text-left" v-html="__mixin_convertNewlinesToHtml(order.line.description)"></div>
                    </tooltip>
                </td>
                <td v-text="order.line.factor" class="text-center" v-if="userCanProduction"></td>

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
                                    :disabled="!userCanProduction"
                            ></option>
                        </select>
                    </div>
                </td>

                <td class="production" v-if="userCanProduction">

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
                                            :disabled="!userCanProduction"
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

                            <hr style="margin: 5px auto">


                            <a class="dropdown-item" href="#"
                               v-if="userCanProduction && canWarehouse(order)"
                               @click="confirmWarehouseModal(order)"
                            >
                                <i class="fa fa-archive" aria-hidden="true"></i> Ustaw status: Magazyn
                            </a>

                            <a class="dropdown-item" href="#"
                               v-if="userCanProduction && canArchive(order)"
                               @click="confirmArchiveModal(order)"
                            >
                                <i class="fa fa-archive" aria-hidden="true"></i> Ustaw status: Archiwum
                            </a>

                            <a class="dropdown-item" href="#"
                               v-if="userCanProduction"
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
            :show="confirmations.warehouse.show"
            @answerYes="updateAgreementStatus(confirmations.warehouse.context, 15)"
            @closeModal="confirmations.warehouse.show = false"
            v-if="confirmations.warehouse.show"
        >
            <div>
                <p><strong>Czy przekazać zamówienie do magazynu:</strong></p>
                <ul class="list-unstyled">
                    <li>id: {{ confirmations.warehouse.context.header.orderNumber}}</li>
                    <li>produkt: {{ confirmations.warehouse.context.product.name }}</li>
                    <li>klient: {{ customerName(confirmations.warehouse.context.customer) }}</li>
                </ul>
            </div>

        </confirmation-modal>

        <confirmation-modal
            :show="confirmations.archive.show"
            @answerYes="updateAgreementStatus(confirmations.archive.context, 20)"
            @closeModal="confirmations.archive.show = false"
            v-if="confirmations.archive.show"
        >
            <div>
                <p><strong>Czy archiwizować zamówienie:</strong></p>
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
                <p>
                    <strong>Czy usunąć zlecenie produkcyjne?</strong>
                    <br>
                    <small class="text-muted">Wszystkie dane dotyczące produkcji zostaną usunięte, a zamówienie otrzyma status 'oczekujące'.</small>
                </p>

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
    import Tooltip from "../base/Tooltip";

    export default {
        name: "ProductionList",

        components: { Dropdown, ConfirmationModal, Filters, TablePlus, Tooltip },

        props: {
            statuses: {
                type: Object,
                default: () => {}
            },
        },

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

                    deleted: false,

                    hideArchive: true,

                    meta: {
                        sort: 'l.confirmedDate:ASC',
                        page: 1,
                    }
                },

                helpers: Helpers,

                orders: [],
                departments: [],

                loading: false,

                confirmations: {
                    warehouse: {
                        show: false,
                        busy: false,
                        context: false,
                    },

                    archive: {
                        show: false,
                        busy: false,
                        context: false,
                    },

                    delete: {
                        show: false,
                        busy: false,
                        context: false,
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
                        all: this.filters.showAll,
                        page: this.filters.meta.page
                    };

                    if (this.filters.dateDelivery.start && String(this.filters.dateDelivery.start).length > 0) {
                        query.dateDeliveryStart = this.filters.dateDelivery.start
                    }

                    if (this.filters.dateDelivery.end && String(this.filters.dateDelivery.end).length > 0) {
                        query.dateDeliveryEnd = this.filters.dateDelivery.end
                    }

                    query.hideArchive = this.filters.hideArchive

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
                        Event.$emit('message', {
                            type: 'success',
                            content: 'Wycofano zlecenie z harmonogramu produkcji.'
                        });
                        Event.$emit('statusUpdated');
                    })
                    .finally(() => {
                        this.confirmations.delete.busy = false;
                        this.confirmations.delete.show = false;
                    });
            },

            updateAgreementStatus(order, newStatus) {
                this.confirmations.archive.busy = true;
                api.setAgreementStatus(order.line.id, newStatus)
                    .then(({data}) => {

                        let idx = this.orders.findIndex((record) => {
                            return record.line.id === order.line.id;
                        });

                        if (idx !== -1) {
                            this.orders.splice(idx, 1);
                        }

                        Event.$emit('message', {
                            type: 'success',
                            content: 'Zapisano zmianę statusu.'
                        });

                    })
                    .finally(() => {
                        this.confirmations.archive.busy = false;
                        this.confirmations.archive.show = false;
                        this.confirmations.warehouse.busy = false;
                        this.confirmations.warehouse.show = false;
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
                    this.filters.dateStart.start = moment().subtract(2, 'M').startOf('month').format('YYYY-MM-DD');
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
                productionApi.updateStatus(productionId, newStatus)
                    .then(() => {
                        Event.$emit('message', {
                            type: 'success',
                            content: 'Zapisano zamianę statusu.'
                        });
                    })
                    .catch((error) => {
                        // TODO: gdy 403 to data zawiera htmla i nie można loopować po błędach
                        Event.$emit('Błąd zapisu.', {
                            type: 'error',
                            content: msg
                        });
                    })
                ;
            },

            getStatusStyle(production) {

                let status = this.helpers.statuses.find(item => item.value === production.status);
                if (status) {
                    return 'background-color: '.concat(status.color);
                }

                return '';
            },

            confirmWarehouseModal(item) {
                this.confirmations.warehouse.context = item;
                this.confirmations.warehouse.show = true;
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
            },

            canArchiveOrWarehouse(order) {
                let lastProductionStage = order.production.data.find(stage => { return stage.departmentSlug === 'dpt05'; });
                return lastProductionStage.status === 3;
            },

            canArchive(order) {
                return order.line.status !== 20 && this.canArchiveOrWarehouse(order);
            },

            canWarehouse(order) {
                return order.line.status !== 15 && this.canArchiveOrWarehouse(order);
            },

            getAgreementStatusName(statusId) {
                return this.statuses[statusId];
            },

            getAgreementStatusClass(statusId) {
                let className = '';
                switch (statusId) {
                    case 5:
                        className = 'badge-danger';
                        break;
                    case 10:
                        className = 'badge-primary';
                        break;
                    case 15:
                        className = 'badge-warning';
                        break;
                    case 20:
                        className = 'badge-success';
                        break;

                    default:
                        className = 'badge-primary'
                }
                return className;
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
                        { name: 'Produkcja', colspan: 5},
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


                if (this.userCanProduction) {
                    headers[0].splice(4, 0, { name: 'Wsp.', sortKey: 'l.factor', rowspan: 2 });
                    headers[0].push({ name: 'Dodatkowe zadania', rowspan: 2});
                }

                headers[0].push({ name: 'Akcje', rowspan: 2 });

                return headers;
            },

            productionSlugs() {
                return this.departments.map(d => { return d.slug; })
            },

            userCanProduction() {
                return this.$user.can(this.$privilages.CAN_PRODUCTION);
            },




        }
    }

</script>

<style scoped lang="scss">

    .table tbody tr {
        text-align: center;
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