<template>
    <div class="modal fade" :class="{show: exposed}" tabindex="-1" :style="getStyle()">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLiveLabel">Planowanie produkcji</h5>
                    <button type="button" class="close" @click.prevent="closeModal()">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>

                <div class="modal-body" ref="scrollableContent">
                    
                    <div v-for="(dpt, key) in dpts" :key="key">

                        <div class="card mb-3">
                            <div class="card-header">
                                {{ dpt.name }}
                            </div>
                            <div class="card-body">

                                <div class="form-row">
                                    <div class="form-group col-sm-4">
                                        <label>Realizacja od:</label>
                                        <DatePicker v-model="dpts[key].dateFrom" />
                                    </div>
                                    <div class="form-group col-sm-4">
                                        <label>Realizacja do:</label>
                                        <DatePicker v-model="dpts[key].dateTo" />
                                    </div>
                                    <div class="form-group col-sm-4">
                                        <label>Status:</label>
                                        <select class="form-control" v-model="dpt.status" :style="getStatusStyle(dpt)">
                                            <option
                                                    v-for="status in statuses"
                                                    :value="status.value"
                                                    v-text="status.name"
                                                    style="background-color: white"
                                            ></option>
                                        </select>
                                    </div>

                                </div>

                                <div class="form-row" v-if="dpt.statusLog && dpt.statusLog.length">
                                    <div class="form-group col-sm-6">

                                        <div class="mb-2">
                                            <a  href="#" @click.prevent="dpt.showHistory = !dpt.showHistory">
                                                <template v-if="dpt.showHistory">
                                                    Ukryj historię zmian statusów
                                                </template>
                                                <template v-else>
                                                    Pokaż historię zmian statusów
                                                </template>
                                            </a>
                                        </div>

                                        <table class="table table-bordered" v-if="dpt.showHistory === true">
                                            <tr>
                                                <th>Data zmiany</th>
                                                <th>Nowy status</th>
                                            </tr>

                                            <tr v-for="status in dpt.statusLog">
                                                <td v-text="status.createdAt"></td>
                                                <td v-text="getStatusName(status.currentStatus)"></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>

                            </div>     
                        </div>            

                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" @click.prevent="closeModal()" :disabled="saving">Anuluj</button>
                    <button type="button" class="btn btn-primary" @click.prevent="save()" :disabled="saving"><i v-if="saving" class="fa fa-spinner fa-pulse fa-fw"></i> Zapisz</button>
                </div>
            </div>
        </div>
    </div>

</template>

<script>

    import DatePicker from "../../base/datepicker";
    import api from '../../../api/neworder';
    import Helpers from '../../../helpers';

    export default {
        name: 'ProductionPlan',
        props: ['showModal', 'departments', 'production', 'orderContext'],
        components: { DatePicker },

        data() {
            return {
                exposed: false,
                saving: false,
                
                dpts: [],
                orderLineId: null,

                statuses: Helpers.statuses
                
            }    
        },

        methods: {
            getStyle() {
                return this.exposed ? 'display: block;' : 'display: none;'
            },

            closeModal(returnData) {

                if (this.saving === false) {
                    this.$emit('closeModal', returnData);
                }
            },

            save() {
                this.saving = true;
                api.storeProductionPlan(this.dpts, this.orderContext)
                .then(({data}) => { 
                    if (Array.isArray(data) && data.length > 0) {
                        this.saving = false;
                        this.closeModal(data);
                    }
                 })
                .catch(() => {})
                .finally(() => {
                    this.saving = false;
                })
            },

            getStatusStyle(production) {

                let status = this.statuses.find(item => item.value == production.status);
                if (status) {
                    return 'background-color: '.concat(status.color);
                }

                return '';
            },

            getStatusName(statusCode) {
                let status = this.statuses.find(item => item.value == statusCode);
                return status ? status.name : 'nieznany';
            }



        },

        watch: {
            showModal(val) {
                this.exposed = val;

                // resetuj scroll po otwarciu modala
                if (val === true) {

                    this.$nextTick(function () {
                        this.$refs.scrollableContent.scrollTop = 0;
                    });

                }
            },

            orderContext(val) {
                this.orderLineId = val;
            },

            departments(val) {
                if (Array.isArray(val)) {

                    this.dpts = [];
                    for (let dpt of val) {

                        this.dpts.push({
                            slug: dpt.slug,
                            name: dpt.name,
                            status: 0,
                            dateFrom: null,
                            dateTo: null,
                            statusLog: [],
                            showHistory: false,
                        });
                    }
                }
            },

            production(val) {
                
                if (Array.isArray(val)) {

                    if (val.length === 0) {
                        this.dpts.forEach(item => { 
                            item.dateFrom = null;
                            item.dateTo = null;
                            item.status = 0;
                        })
                    }

                    for (let prod of val) {
                        
                        let index = this.dpts.findIndex(item => { return item.slug === prod.departmentSlug; });
                        if (index !== -1) {
                            this.dpts[index].dateFrom = prod.dateStart;
                            this.dpts[index].dateTo = prod.dateEnd;
                            this.dpts[index].status = prod.status;
                            this.dpts[index].statusLog = prod.statusLog;

                        }
                    }

                }
            }
        }
    }

</script>