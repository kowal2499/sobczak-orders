<template>
    <tr :class="{'is-disabled': disabled}">
        <td :colspan="columnsCount">
            <div class="d-flex align-items-start gap-3">
                <attachments-widget
                    :attachments="order.attachments || []"
                    :show-name="false"
                    :tooltip="true"
                    :horizontal="true"
                />

                <div class="custom-tasks">
                    <div class="task custom-task d-flex flex-column gap-1" v-for="task in getCustomTasks(order.productions)">
                        <label>{{ task.title }}</label>

                        <b-dropdown
                                :text="$t(getStatusData(task.status).name)"
                                size="sm"
                                :class="getStatusData(task.status).className"
                                :disabled="disabled"
                                variant="light"
                                class="w-100"
                        >
                            <b-dropdown-item
                                    v-for="status in helpers.statusesPerTaskType(task.departmentSlug)"
                                    :value="status.value"
                                    :key="status.value"
                                    :disabled="!userCanProduction"
                                    @click="updateTask(task, status.value)"
                            >{{ $t(status.name) }}</b-dropdown-item>
                        </b-dropdown>

                        <div class="text-center text-nowrap w-100">
                            <span v-if="task.dateStart">
                                {{ task.dateStart | formatDate('YYYY-MM-DD') }}
                            </span>
                            <span v-else class="text-muted text-sm text-nowrap opacity-75">
                                <i class="fa fa-ban mr-1" /> {{ $t('noData') }}
                            </span>
                        </div>

                        <div class="text-center text-nowrap w-100">
                            <span v-if="task.dateEnd">
                                {{ task.dateEnd | formatDate('YYYY-MM-DD') }}
                            </span>
                            <span v-else class="text-muted text-sm text-nowrap opacity-75">
                                <i class="fa fa-ban mr-1" /> {{ $t('noData') }}
                            </span>
                        </div>

                        <production-task-notification
                            class="w-100"
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
    import AttachmentsWidget from "../../../components/orders/single/AttachmentsWidget";
    import helpers from "../../../helpers";
    import ProductionTaskNotification from "../../../components/production/ProductionTaskNotification";

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
            columnsCount: Number
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

    .is-disabled {
        opacity: 0.5
    }

    .custom-tasks {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;

        .custom-task {
            width: 190px;
        }
    }
</style>