<template>
    <div>
        <div class="row" v-if="selectedIndex !== -1">

            <div class="col-md-4">
                <div class="nav flex-column nav-pills border-right pr-2">
                    <a v-for="(task, index) in tasks"
                       class="nav-link" id="v-pills-messages-tab" href="#"
                       :class="{active: index === selectedIndex}"
                       @click.prevent="selectedIndex = index"
                       v-if="taskTypes.indexOf(task.departmentSlug) !== -1"
                    >
                        <span class="statusNotify"
                              :style="getStatusStyle(task.status)"/>
                        {{ $t(task.title) }}
                    </a>
                </div>
            </div>

            <div class="col-md-8">

                <div class="row" v-for="(task, index) in tasks">

                    <template v-if="index === selectedIndex">
                        <div class="col-sm-10">

                            <div class="form-row" v-if="canAdd">
                                <div class="col-sm-8">
                                    <div class="form-group">
                                        <label>{{ $t('orders.title') }}</label>
                                        <input type="text" class="form-control" v-model="task.title">
                                    </div>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="col-sm-10">
                                    <div class="form-group">
                                        <label>{{ $t('orders.description') }}</label>
                                        <textarea class="form-control" v-model="task.description" :disabled="!canEditLine()"></textarea>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-row">

                                <div class="col-sm-8">
                                    <div class="form-group">
                                        <label>{{ $t('orders.status') }}</label>
                                        <select class="form-control" v-model="task.status" :style="getStatusStyle(task.status)" :disabled="!canEditLine()">
                                            <option
                                                    v-for="status in helpers.statusesPerTaskType(task.departmentSlug)"
                                                    :value="status.value"
                                                    v-text="$t(status.name)"
                                                    style="background-color: white"
                                            />
                                        </select>
                                    </div>
                                </div>

                            </div>


                            <div class="form-row" v-if="canEditLine()">
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label>{{ $t('orders.realizationFrom') }}</label><br>
                                        <date-picker v-model="task.dateStart" :is-range="false" style="width: 100%"/>
                                    </div>
                                </div>

                                <div class="col-lg-6" v-if="canEditLine()">
                                    <div class="form-group">
                                        <label>{{ $t('orders.realizationTo') }}</label><br>
                                        <date-picker v-model="task.dateEnd" :is-range="false" style="width: 100%"/>
                                    </div>
                                </div>
                            </div>


                            <div class="mb-2">
                                <a href="#" @click.prevent="toggleHistory(task)">
                                    <template v-if="showHistoryForSlugs.indexOf(task.departmentSlug) !== -1">
                                        {{ $t('orders.hideStatuses') }}
                                    </template>
                                    <template v-else>
                                        {{ $t('orders.showStatuses') }}
                                    </template>
                                </a>
                            </div>

                            <table class="table table-bordered" v-if="showHistoryForSlugs.indexOf(task.departmentSlug) !== -1">
                                <tr>
                                    <th>{{ $t('orders.newStatus') }}</th>
                                    <th>{{ $t('orders.dateOfChange') }}</th>
                                    <th>{{ $t('orders.user') }}</th>
                                </tr>

                                <tr v-for="status in task.statusLogs">
                                    <td>{{ getStatusName(status.currentStatus) }}</td>
                                    <td>{{ status.createdAt | formatDate() }}</td>
                                    <td>{{ status.user.userFullName }}</td>
                                </tr>
                            </table>
                        </div>

                        <div class="col-sm-2" v-if="canAdd">
                            <button href="#" class="d-none d-sm-inline-block btn btn-sm btn-light shadow-sm float-right" @click.prevent="confirmDeleteModal(task, index)">
                                <i class="fa fa-trash text-danger"/>
                            </button>
                        </div>

                    </template>

                </div>

            </div>

        </div>

        <div class="row" v-else>
            <div class="col">
                <div class="alert alert-info">{{ $t('orders.noAdditionalOrders') }}</div>
            </div>
        </div>

        <div class="row" v-if="canAdd">
            <div class="col">
                <hr>
                <button href="#" class="d-none d-sm-inline-block btn btn-sm btn-success shadow-sm mb-3 float-right" @click.prevent="add">
                    <i class="fa fa-plus"/>
                    <span class="pl-1">{{ $t('orders.newTask') }}</span>
                </button>
            </div>
        </div>

        <confirmation-modal
            :show="confirmations.delete.show"
            @closeModal="confirmations.delete.show = false"
            @answerYes="handleDelete()"
        >
            <div v-if="confirmations.delete.show">
                {{ $t('orders.shouldDeleteTask') }} '{{ confirmations.delete.context.title }}'?
            </div>
        </confirmation-modal>

    </div>
</template>

<script>
    import DatePicker from "../../base/DatePicker";
    import Helpers from "../../../helpers";
    import ConfirmationModal from "../../base/ConfirmationModal";

    export default {
        name: "ProductionWidget",

        props: {
            value: {},

            taskTypes: {
                type: Array,
                default: () => {}
            },

            canAdd: {
                type: Boolean,
                default: false
            }
        },

        components: { DatePicker, ConfirmationModal },

        data() {
            return {
                helpers: Helpers,
                tasks: this.value,
                showHistoryForSlugs: [],
                selectedIndex: this.getFirstItemIndex(),

                confirmations: {
                    delete : {
                        show: false
                    }
                }
            }
        },

        watch: {
            tasks: {
                handler(val) {
                    this.$emit('input', val)
                },
                deep: true
            }
        },

        methods: {
            taskTypeChange(task) {
                this.selectedTaskType = task.departmentSlug;
            },

            getStatusStyleBySlug(slug) {
                let dpt = this.tasks.find(task => { return task.departmentSlug === slug });
                if (dpt) {
                    return this.getStatusStyle(dpt.status)
                } else {
                    return '';
                }
            },

            getStatusStyle(statusId) {
                let status = this.helpers.statuses.find(item => item.value === parseInt(statusId));
                if (status) {
                    return 'background-color: '.concat(status.color);
                }
                return '';
            },

            getStatusName(statusCode) {
                let status = this.helpers.statuses.find(item => item.value === parseInt(statusCode));
                return this.$t(status ? status.name : 'nieznany');
            },

            toggleHistory(dpt) {
                let idx = this.showHistoryForSlugs.indexOf(dpt.departmentSlug);
                if (idx !== -1) {
                    this.showHistoryForSlugs.splice(idx, 1);
                } else {
                    this.showHistoryForSlugs.push(dpt.departmentSlug);
                }
            },

            getFirstItemIndex() {
                let index = 0;

                for (let i of this.value) {
                    if (this.taskTypes.indexOf(i.departmentSlug) !== -1) {
                        return index;
                    }
                    index++;
                }

                return -1;
            },

            add() {
                this.tasks.push({
                    dateStart: null,
                    dateEnd: null,
                    departmentSlug: 'custom_task',
                    description: null,
                    id: null,
                    title: this.$t('orders.newTask'),
                    status: 10
                });

                this.selectedIndex = this.getFirstItemIndex();
            },

            confirmDeleteModal(task, index) {
                this.confirmations.delete.context = task;
                this.confirmations.delete.show = true;
                this.confirmations.delete.index = index;
            },

            handleDelete() {
                let idx = 0;
                let reduced = [];
                for (let item of this.tasks) {
                    if (idx++ !== this.confirmations.delete.index) {
                        reduced.push(item)
                    }
                }
                this.tasks = reduced;
                this.confirmations.delete.show = false;

                if (this.tasks.length === 5) {
                    this.selectedIndex = -1;
                } else {
                    this.selectedIndex = this.getFirstItemIndex();
                }
            },

            canEditLine() {
                return this.$user.can(this.$privilages.CAN_PRODUCTION);
            }

        }
    }
</script>

<style scoped lang="scss">
    .statusNotify {
        display: inline-block; width: 12px; height: 12px; margin-right: 5px; border: 1px solid #666; background-color: white;
    }
</style>