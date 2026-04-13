<script>
import CollapsibleList from '../../../components/base/CollapsibleList'
import helpers from "@/helpers";
import ProductionTaskNotification from "../../../components/production/ProductionTaskNotification";
import StatusDropdown from "../../../components/base/StatusDropdown";
import { canEditTask } from "../../task/specification/canEditTask";

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
        CollapsibleList,
        ProductionTaskNotification,
        StatusDropdown,
    },

    computed: {
        statusOptions() {
            return helpers.statusesPerTaskType('custom_task')
        }
    },

    methods: {
        updateTask(task, newStatus) {
            task.status = newStatus;
            this.$emit('taskStatusUpdated', { id: task.id, status: newStatus});
        },

        statusOptionsForTask(task) {
            const owner = task.ownerId ? { id: task.ownerId } : null;
            const editable = canEditTask(owner, this.$user.getId());
            return this.statusOptions.map(opt => ({ ...opt, disabled: !editable }));
        },

        statusesPerTaskType: helpers.statusesPerTaskType
    },
}
</script>

<template>
    <CollapsibleList :items="tasks" :visible-rows="1">
        <template #default="{ item, index }">
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
    </CollapsibleList>

</template>

<style scoped lang="scss">
.custom-task {
    width: 180px;
}
</style>