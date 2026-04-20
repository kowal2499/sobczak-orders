<script>
import TaskItem from "../components/TaskItem.vue";
import { findTasks, createTask, updateTask, deleteTask } from "../repository/taskRepository";
import { TASK_TYPE_CUSTOM } from "../configuration/taskDefinitions";
import { TASK_STATUS_CUSTOM_TO_ORDER } from "@/modules/task/configuration/taskStatuses";
import { v4 as uuid } from "uuid";

const taskFactory = (data = {}) => ({
    id: data.id || null,
    type: TASK_TYPE_CUSTOM,
    title: data.title || null,
    description: data.description || null,
    dateStart: data.dateStart || null,
    dateEnd: data.dateEnd || null,
    status: data.status || TASK_STATUS_CUSTOM_TO_ORDER,
    agreementLineId: data.agreementLineId || null,
    createdAt: data.createdAt || null,
    updatedAt: data.updatedAt || null,
    owner: data.owner || null,
    statusLogs: data.statusLogs || [],
    _id: data.id ? String(data.id) : uuid()
});

export default {
    name: "TasksView",
    props: {
        agreementLineId: {
            type: Number,
            required: true
        },
        showSaveButton: {
            type: Boolean,
            default: true
        }
    },
    components: {
        TaskItem
    },

    created() {
        this.fetchTasks();
    },

    methods: {
        async fetchTasks() {
            this.isBusy = true;
            try {
                const { data } = await findTasks({
                    agreementLineId: this.agreementLineId,
                });
                this.tasks = data.map(taskFactory);
                this.originalTasks = JSON.parse(JSON.stringify(this.tasks));
            } finally {
                this.isBusy = false;
            }
        },
        hasTaskChanged(task) {
            if (!task.id) return true;
            const original = this.originalTasks.find(t => t._id === task._id);
            if (!original) return true;
            return JSON.stringify(task) !== JSON.stringify(original);
        },
        addTask() {
            this.tasks.push(taskFactory({ agreementLineId: this.agreementLineId }));
        },
        updateTask(updatedTask) {
            this.tasks = this.tasks.map(task =>
                task._id === updatedTask._id ? updatedTask : task
            );
        },
        removeTask(task) {
            if (task.id) {
                this.pendingDeletes.push(task.id);
            }
            this.tasks = this.tasks.filter(t => t._id !== task._id);
        },
        async validate() {
            const items = this.$refs.taskItems || [];
            const results = await Promise.all(items.map(item => item.validate()));
            return results.every(r => r);
        },
        async save() {
            if (!await this.validate()) return;

            this.isBusy = true;
            try {
                for (const id of this.pendingDeletes) {
                    await deleteTask(id);
                }
                this.pendingDeletes = [];
                for (const task of this.tasks) {
                    if (!this.hasTaskChanged(task)) continue;
                    if (task.id) {
                        await updateTask(task.id, task);
                    } else {
                        await createTask({ ...task, agreementLineId: this.agreementLineId });
                    }
                }
                await this.fetchTasks();
            } finally {
                this.isBusy = false;
            }
        }
    },

    data: () => ({
        tasks: [],
        originalTasks: [],
        pendingDeletes: [],
        isBusy: false
    })
};
</script>

<template>
    <div class="d-flex flex-column gap-3">
        <div class="alert alert-info" v-if="tasks.length === 0 && !isBusy">
            <font-awesome-icon icon="info-circle" /> {{ $t('task.noTasks') }}
        </div>

        <div class="d-flex flex-column gap-3">
            <TaskItem
                v-for="task in tasks"
                :key="task._id"
                ref="taskItems"
                :value="task"
                @input="updateTask"
                @remove="removeTask"
            />
        </div>

        <div class="d-flex gap-2 justify-content-end">
            <button v-if="$user.can('task.orphans:create')" class="btn btn-success btn-sm" :disabled="isBusy" @click="addTask">
                <font-awesome-icon icon="plus" /> {{ $t('task.addTask') }}
            </button>
            <button v-show="showSaveButton" class="btn btn-primary btn-sm" :disabled="isBusy" @click="save">
                <font-awesome-icon icon="save" /> {{ $t('task.save') }}
            </button>
        </div>
    </div>
</template>

<style scoped lang="scss">

</style>
