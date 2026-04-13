<script>
import ShowcaseBadge from "@/components/base/Showcase/ShowcaseBadge";
import DatePicker from "@/components/base/DatePicker";
import StatusDropdown from "@/components/base/StatusDropdown";
import ConfirmationModal from "@/components/base/ConfirmationModal";
import { getTaskStatuses } from "../configuration/taskStatuses";
import { getTaskDefinition, TASK_TYPE_CUSTOM } from "../configuration/taskDefinitions";
import { canEditTask } from "../specification/canEditTask";
import proxyValue from "@/mixins/proxyValue";

export default {
    name: "TaskItem",
    components: {
        ShowcaseBadge,
        DatePicker,
        StatusDropdown,
        ConfirmationModal,
    },

    mixins: [proxyValue],

    data() {
        return {
            isExpanded: !this.value.id,
            showDeleteModal: false,
        };
    },

    computed: {
        taskDefinition() {
            return getTaskDefinition(TASK_TYPE_CUSTOM);
        },
        statusOptions() {
            return getTaskStatuses(TASK_TYPE_CUSTOM);
        },
        isNew() {
            return !this.proxyData.id;
        },
        canEdit() {
            return canEditTask(this.proxyData.owner, this.$user.getId());
        },
        editableStatusOptions() {
            const editable = canEditTask(this.proxyData.owner, this.$user.getId());
            return this.statusOptions.map(opt => ({ ...opt, disabled: !editable }));
        },
        sortedStatusLogs() {
            return (this.proxyData.statusLogs || []).slice().reverse();
        },
    },

    methods: {
        toggleExpand() {
            this.isExpanded = !this.isExpanded;
        },
        statusName(value) {
            const opt = this.statusOptions.find(o => o.value === value);
            return opt ? opt.name : "—";
        },
        validate() {
            return this.$refs.observer.validate();
        },
    },
};
</script>

<template>
    <ValidationObserver ref="observer" tag="div" class="task-item border rounded details p-0">

        <!-- Compact row -->
        <div class="task-item__row d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center gap-2 px-3 py-2">

            <ValidationProvider
                :name="$t('task.title')"
                #default="{ errors }"
                :rules="canEdit ? 'required' : ''"
                tag="div"
                class="task-item__col"
            >
                <b-form-input
                    v-model="proxyData.title"
                    :placeholder="$t('task.title')"
                    size="sm"
                    :disabled="!canEdit"
                    :state="errors.length > 0 ? false : null"
                />
                <div class="invalid-feedback d-block" v-if="errors.length">{{ errors[0] }}</div>
            </ValidationProvider>

            <ValidationProvider
                :name="$t('task.dateFrom')"
                #default="{ errors }"
                :rules="canEdit ? { required: true, dateFromOrEqual: { target: proxyData.dateEnd } } : {}"
                tag="div"
                class="task-item__col"
            >
                <date-picker
                    v-model="proxyData.dateStart"
                    :is-range="false"
                    :date-only="true"
                    :is-disabled="!canEdit"
                    style="width: 100%"
                    :class="{ 'is-invalid': errors.length > 0 }"
                />
                <div class="invalid-feedback d-block" v-if="errors.length">{{ errors[0] }}</div>
            </ValidationProvider>

            <ValidationProvider
                :name="$t('task.dateTo')"
                #default="{ errors }"
                :rules="canEdit ? { required: true, dateToOrEqual: { target: proxyData.dateStart } } : {}"
                tag="div"
                class="task-item__col"
            >
                <date-picker
                    v-model="proxyData.dateEnd"
                    :is-range="false"
                    :date-only="true"
                    :is-disabled="!canEdit"
                    style="width: 100%"
                    :class="{ 'is-invalid': errors.length > 0 }"
                />
                <div class="invalid-feedback d-block" v-if="errors.length">{{ errors[0] }}</div>
            </ValidationProvider>

            <status-dropdown
                v-model="proxyData.status"
                :options="editableStatusOptions"
                class="task-item__col"
            />

            <button
                class="btn btn-sm btn-link text-secondary p-1 flex-shrink-0 align-self-end align-self-lg-center"
                @click="toggleExpand"
                :title="isExpanded ? $t('task.collapse') : $t('task.expand')"
                style="width: 30px"
            >
                <font-awesome-icon :icon="isExpanded ? 'chevron-up' : 'chevron-down'" />
            </button>
        </div>

        <!-- Expanded section -->
        <div v-show="isExpanded" class="task-item__expanded border-top px-3 py-3 bg-white">
            <showcase-badge :label="$t('task.description')">
                <template #value>
                    <b-textarea v-model="proxyData.description" rows="3" :disabled="!canEdit" />
                </template>
            </showcase-badge>

            <div class="d-flex flex-wrap gap-3 mt-3">
                <showcase-badge v-if="proxyData.owner" :label="$t('task.owner')">
                    <template #value>
                        <span class="text-muted">{{ proxyData.owner.userFullName }}</span>
                    </template>
                </showcase-badge>

                <showcase-badge v-if="proxyData.createdAt" :label="$t('task.createdAt')">
                    <template #value>
                        <div class="d-flex gap-4">
                            <div>{{ proxyData.createdAt | formatDate('YYYY-MM-DD hh:mm') }}</div>
                            <div class="text-secondary">{{ proxyData.createdAt | timeago }}</div>
                        </div>
                    </template>
                </showcase-badge>
            </div>

            <showcase-badge v-if="proxyData.statusLogs && proxyData.statusLogs.length" :label="$t('task.statusHistory')" class="mt-4">
                <template #value>
                    <table class="table table-sm table-borderless mb-0">
                        <tbody>
                        <tr v-for="log in sortedStatusLogs" :key="log.id">
                            <td class="pl-0 text-nowrap">
                                    <span v-if="log.previousStatus !== null" class="text-muted">
                                        {{ statusName(log.previousStatus) }}
                                        <font-awesome-icon icon="arrow-right" class="mx-1" />
                                    </span>
                                <strong class="text-primary">{{ statusName(log.currentStatus) }}</strong>
                            </td>
                            <td class="text-muted text-nowrap">
                                <div>{{ log.createdAt | formatDate('YYYY-MM-DD hh:mm') }}</div>
                                <div class="text-secondary">{{ log.createdAt | timeago }}</div>
                            </td>
                            <td class="text-muted">{{ log.user ? log.user.userFullName : '' }}</td>
                        </tr>
                        </tbody>
                    </table>
                </template>
            </showcase-badge>

            <div v-if="canEdit" class="d-flex justify-content-end mt-3">
                <button class="btn btn-danger btn-sm" @click="showDeleteModal = true">
                    <font-awesome-icon icon="trash" />
                </button>
            </div>
        </div>

        <confirmation-modal
            :show="showDeleteModal"
            @closeModal="showDeleteModal = false"
            @answerYes="$emit('remove', proxyData); showDeleteModal = false"
        >
            {{ $t('orders.shouldDeleteTask') }} <strong>{{ proxyData.title || '' }}</strong>?
        </confirmation-modal>

    </ValidationObserver>
</template>

<style scoped lang="scss">
.task-item__row {
    gap: 0.5rem;
}

.task-item__col {
    flex: 1 1 0;
    min-width: 0;
    width: 100%;
}

.task-item__expanded .table {
    font-size: 0.8rem;
}
</style>
