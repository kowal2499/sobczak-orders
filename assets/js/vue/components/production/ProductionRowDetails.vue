<template>
    <tr>
        <td colspan="5">

            <attachments-widget
                :attachments="order.header.attachments || []"
                :show-name="false"
                :tooltip="true"
                :horizontal="true"
            ></attachments-widget>
        </td>

        <td colspan="5" class="tasks">
            <div class="custom-tasks">
                <div class="task custom-task" v-for="task in getCustomTasks(order.production.data)">
                    <label>{{ task.title }}</label>
                    <select class="form-control"
                            v-model="task.status"
                            @change="$emit('statusUpdated', { id: task.id, status: task.status })"
                            :style="getStatusStyle(task)"
                            :disabled="!userCanProduction"
                    >
                        <option
                                v-for="status in helpers.statusesPerTaskType(task.departmentSlug)"
                                :value="status.value"
                                v-text="$t(status.name)"
                                style="background-color: white"
                        ></option>
                    </select>
                </div>
            </div>
        </td>
    </tr>
</template>

<script>

    import ProductionRowBase from "./ProductionRowBase";
    import AttachmentsWidget from "../orders/single/AttachmentsWidget";

    export default {
        name: "ProductionRowDetails",

        extends: ProductionRowBase,

        components: {AttachmentsWidget},

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