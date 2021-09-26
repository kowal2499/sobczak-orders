<template>
    <tr>
        <td colspan="5">

            <attachments-widget
                :attachments="order.Agreement.attachments || []"
                :show-name="false"
                :tooltip="true"
                :horizontal="true"
            />
        </td>

        <td colspan="5" class="tasks">
            <div class="custom-tasks">
                <div class="task custom-task" v-for="task in getCustomTasks(order.productions)">
                    <label>{{ task.title }}</label>

                    <b-dropdown
                            :text="$t(getStatusData(task.status).name)"
                            size="sm"
                            :class="getStatusData(task.status).className"
                            variant="light"
                    >
                        <b-dropdown-item
                                v-for="status in helpers.statusesPerTaskType(task.departmentSlug)"
                                :value="status.value"
                                :key="status.value"
                                :disabled="!userCanProduction()"
                                @click="updateTask(task, status.value)"
                        >{{ $t(status.name) }}</b-dropdown-item>
                    </b-dropdown>
                    <div>
                        <production-task-notification
                            :date-start="task.dateStart"
                            :date-end="task.dateEnd"
                            :status="task.status"
                            :isStartDelayed="task.isStartDelayed"
                            :isCompleted="task.isCompleted"
                            :date-deadline="order.confirmedDate"
                        />
                    </div>

                </div>
            </div>
        </td>
    </tr>
</template>

<script>

    import ProductionRowBase from "./ProductionRowBase";
    import AttachmentsWidget from "../orders/single/AttachmentsWidget";
    import helpers from "../../helpers";
    import ProductionTaskNotification from "./ProductionTaskNotification";

    export default {
        name: "ProductionRowDetails",

        extends: ProductionRowBase,

        components: {AttachmentsWidget, ProductionTaskNotification},

        props: {
            order: {
                type: Object,
                default: () => {}
            },
            statuses: {
                type: Object,
                default: () => {}
            },
        },

        methods: {
            getCustomTasks(production) {
                return production.filter(p => { return p.departmentSlug === 'custom_task' ? p : false; })
            },

            getStatusData(status) {
                return helpers.statuses.find(i => i.value === parseInt(status));
            },

            updateTask(task, newStatus) {
                task.status = newStatus;
                this.$emit('statusUpdated', { id: task.id, status: newStatus});
            }
        }
    }
</script>

<style  lang="scss" scoped>
    .custom-tasks {
        display: flex;
        flex-wrap: wrap;

        .custom-task {
            width: 170px;
            margin-right: 20px;
        }
    }
</style>