<script>
import CollapsibleList from '../../../components/base/CollapsibleList'
import helpers from "@/helpers";
import ProductionTaskNotification from "../../../components/production/ProductionTaskNotification";

export default {
    name: 'Tasks',

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
        ProductionTaskNotification
    },

    computed: {
        statusOptions() {
            return helpers.statusesPerTaskType('custom_task')
        }

    },

    methods: {
        getStatusData(status) {
            return helpers.statuses.find(i => i.value === parseInt(status));
        },

        updateTask(task, newStatus) {
            task.status = newStatus;
            this.$emit('taskStatusUpdated', { id: task.id, status: newStatus});
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
                <b-dropdown
                    :text="$t(getStatusData(item.status).name)"
                    size="sm"
                    :class="getStatusData(item.status).className"
                    variant="light"
                    class="w-100"
                >
                    <b-dropdown-item
                        v-for="status in statusOptions"
                        :value="status.value"
                        :key="status.value"
                        @click="updateTask(item, status.value)"
                    >{{ $t(status.name) }}</b-dropdown-item>
                </b-dropdown>

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