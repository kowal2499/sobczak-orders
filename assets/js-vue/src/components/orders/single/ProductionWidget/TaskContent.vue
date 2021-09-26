<template>
    <b-row>
        <b-col sm="10">
            <b-row v-if="isCustomTask">
                <b-col sm="8">
                    <b-form-group :label="$t('orders.title')">
                        <b-form-input v-model="proxyData.title"/>
                    </b-form-group>
                </b-col>
            </b-row>
            <b-row>
                <b-col sm="10">
                    <b-form-group :label="$t('orders.description')">
                        <b-textarea v-model="proxyData.description"></b-textarea>
                    </b-form-group>
                </b-col>
            </b-row>
            <b-row>
                <b-col sm="8">
                    <b-form-group :label="$t('orders.status')">
                        <b-form-select
                            v-model="proxyData.status"
                            :style="statusStyle"
                            :disabled="!canEdit"
                        >
                            <b-form-select-option
                                v-for="status in statuses"
                                :key="status.value"
                                :value="String(status.value)"
                                style="background-color: white"
                            >
                                {{ $t(status.name) }}
                            </b-form-select-option>
                        </b-form-select>
                    </b-form-group>
                </b-col>
            </b-row>

            <b-row v-if="canEdit">
                <b-col lg="6">
                    <b-form-group :label="$t('orders.realizationFrom')">
                        <date-picker v-model="proxyData.dateStart" :is-range="false" :date-only="false" style="width: 100%"/>
                    </b-form-group>
                </b-col>
                <b-col lg="6">
                    <b-form-group :label="$t('orders.realizationTo')">
                        <date-picker v-model="proxyData.dateEnd" :is-range="false" :date-only="false" style="width: 100%"/>
                    </b-form-group>
                </b-col>
            </b-row>

            <b-row>
                <b-col>
                    <div class="text-right">
                        <a href="#" @click.prevent="showHistory = !showHistory">
                            {{ showHistory ? $t('orders.hideStatuses') : $t('orders.showStatuses') }}
                        </a>
                    </div>
                    <table class="table mt-2" v-if="showHistory">
                        <tr>
                            <th>{{ $t('orders.newStatus') }}</th>
                            <th>{{ $t('orders.dateOfChange') }}</th>
                            <th>{{ $t('orders.user') }}</th>
                        </tr>

                        <tr v-for="status in proxyData.statusLogs">
                            <td>{{ getStatusNameByCode(status.currentStatus) }}</td>
                            <td>{{ status.createdAt | formatDate() }}</td>
                            <td>{{ status.user ? status.user.userFullName : '' }}</td>
                        </tr>
                    </table>
                </b-col>
            </b-row>
        </b-col>
        <b-col sm="2" v-if="isCustomTask">
            <button href="#" class="d-sm-inline-block btn btn-sm btn-light shadow-sm float-right"
                    @click.prevent="showDeleteModal = true"
            >
                <i class="fa fa-trash text-danger"/>
            </button>
        </b-col>

        <confirmation-modal
            :show="showDeleteModal"
            @closeModal="showDeleteModal = false"
            @answerYes="handleDelete()"
        >
            {{ $t('orders.shouldDeleteTask') }} '{{ proxyData.title }}'?
        </confirmation-modal>
    </b-row>
</template>

<script>
import proxyValue from "../../../../mixins/proxyValue";
import helpers from "../../../../helpers";
import DatePicker from "../../../base/DatePicker";
import ConfirmationModal from "../../../base/ConfirmationModal";
import moment from "moment";

export default {
    name: "TaskContent",
    mixins: [proxyValue],
    props: {
        canEdit: {
            type: Boolean,
            default: true
        }
    },
    components: { DatePicker, ConfirmationModal },
    computed: {
        statuses() {
            return helpers.statusesPerTaskType(this.proxyData.departmentSlug);
        },
        statusStyle() {
            return helpers.getStatusStyle(this.proxyData.status)
        },
        isCustomTask() {
            return this.proxyData.departmentSlug === 'custom_task';
        }
    },
    watch: {
        'proxyData.status'() {
            // remove not saved logs
            this.proxyData.statusLogs = this.proxyData.statusLogs.filter(log => log.id !== null);
            // add new log
            this.proxyData.statusLogs.push({
                id: null,
                currentStatus: this.proxyData.status,
                createdAt: (new moment()).format('YYYY-MM-DD HH:mm:ss'),
                user: {
                    id: this.$user.getId(),
                    userFullName: this.$user.getName(),
                }
            });
        },
    },
    methods: {
        getStatusNameByCode(statusCode) {
            let status = helpers.statuses.find(item => item.value === parseInt(statusCode));
            return this.$t(status ? status.name : 'nieznany');
        },
        handleDelete() {
            // generate temporary id
            this.proxyData.id = Date.now();
            this.$nextTick(() => {
                this.showDeleteModal = false;
                this.$emit('delete', this.proxyData.id);
            })

        }
    },
    data: () => ({
        showHistory: false,
        oldStatus: '',
        showDeleteModal: false,
    })
}
</script>

<style scoped>

</style>