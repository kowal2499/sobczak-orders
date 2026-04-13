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
            } finally {
                this.isBusy = false;
            }
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
        async save() {
            this.isBusy = true;
            try {
                for (const id of this.pendingDeletes) {
                    await deleteTask(id);
                }
                this.pendingDeletes = [];
                for (const task of this.tasks) {
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
        pendingDeletes: [],
        isBusy: false
    })
};
</script>

<template>
    <div class="d-flex flex-column gap-3">
        <div class="alert alert-info" v-if="tasks.length === 0 && !isBusy">
            <font-awesome-icon icon="info-circle" /> Brak zadań
        </div>

        <div class="d-flex flex-column gap-3">
            <TaskItem
                v-for="task in tasks"
                :key="task._id"
                :value="task"
                @input="updateTask"
                @remove="removeTask"
            />
        </div>

        <div class="d-flex gap-2 justify-content-end">
            <button class="btn btn-success btn-sm" :disabled="isBusy" @click="addTask">
                <font-awesome-icon icon="plus" /> Dodaj
            </button>
            <button v-show="showSaveButton" class="btn btn-primary btn-sm" :disabled="isBusy" @click="save">
                <font-awesome-icon icon="save" /> Zapisz
            </button>
        </div>
    </div>
</template>

<style scoped lang="scss">

</style>
