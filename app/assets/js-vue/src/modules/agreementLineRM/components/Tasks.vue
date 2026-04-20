<script>
import DropdownList from '../../../components/base/DropdownList'
import ProductionTaskNotification from "../../../components/production/ProductionTaskNotification";
import StatusDropdown from "../../../components/base/StatusDropdown";
import { canEditTask } from "../../task/specification/canEditTask";
import { getTaskStatuses } from "@/modules/task/configuration/taskStatuses";
import { TASK_TYPE_CUSTOM } from "@/modules/task/configuration/taskDefinitions";

export default {
    name: "Tasks",

    props: {
        tasks: {
            type: Array,
            default: () => []
        },
        deadline: {
            type: String
        }
    },

    components: {
        DropdownList,
        ProductionTaskNotification,
        StatusDropdown,
    },

    methods: {
        updateTask(task, newStatus) {
            task.status = newStatus;
            this.$emit('taskStatusUpdated', { id: task.id, status: newStatus});
        },

        statusOptionsForTask(task) {
            const owner = task.ownerId ? { id: task.ownerId } : null;
            const editable = canEditTask(owner, this.$user.getId());
            return getTaskStatuses(TASK_TYPE_CUSTOM).map(opt => ({ ...opt, disabled: !editable }))
        },
    },
}
</script>

<template>
    <DropdownList :items="tasks">
        <template #default="{ item }">
            <div class="custom-task d-flex flex-column" style="gap: 2px">
                <label class="m-0">{{ item.title || '' }}</label>
                <status-dropdown
                    :value="item.status"
                    :options="statusOptionsForTask(item)"
                    @input="updateTask(item, $event)"
                />

                <div class="d-flex gap-1 justify-content-between">
                    <div class="text-nowrap flex-1">
                        <span v-if="item.dateStart">
                            {{ item.dateStart | formatDate('YYYY-MM-DD') }}
                        </span>
                        <span v-else class="text-muted text-sm text-nowrap opacity-75">
                            <i class="fa fa-ban mr-1" /> {{ $t('noData') }}
                        </span>
                    </div>

                    <div class="text-nowrap flex-1">
                        <span v-if="item.dateEnd">
                            {{ item.dateEnd | formatDate('YYYY-MM-DD') }}
                        </span>
                        <span v-else class="text-muted text-sm text-nowrap opacity-75">
                            <i class="fa fa-ban mr-1" /> {{ $t('noData') }}
                        </span>
                    </div>
                </div>

                <production-task-notification
                    class="w-100"
                    :date-start="item.dateStart"
                    :date-end="item.dateEnd"
                    :status="item.status"
                    :isStartDelayed="item.isStartDelayed"
                    :isCompleted="item.isCompleted"
                    :date-deadline="deadline"
                />
            </div>
        </template>
    </DropdownList>

</template>

<style scoped lang="scss">
.custom-task {
    width: 180px;
}
</style>