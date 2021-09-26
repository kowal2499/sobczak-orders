<template>
    <div>
        <b-tabs pills card vertical nav-wrapper-class="production-tab-panel">
            <b-tab
                v-for="tab in tabs"
                :key="tab.index"
                :disabled="false === tab.enabled"
            >
                <template #title>
                    <span v-if="tab.enabled">
                        <span class="statusNotify" :style="getStatusStyle(tab.status)"/> {{ tab.title }}
                    </span>
                    <hr v-else>
                </template>

                <task-content
                    v-model="proxyData.tasks[tab.index]"
                    :can-edit="canEdit"
                    @delete="handleDelete"
                />
            </b-tab>
        </b-tabs>
        <hr>
        <TagsWidget module-name="production" v-model="proxyData.tags" :logs="proxyData.tagsData"></TagsWidget>
    </div>
</template>

<script>
    import DatePicker from "../../base/DatePicker";
    import ConfirmationModal from "../../base/ConfirmationModal";
    import TagsWidget from "../../../modules/tags/widget/TagsWidget";
    import TaskContent from "./ProductionWidget/TaskContent";
    import proxyValue from "../../../mixins/proxyValue";
    import helpers from "../../../helpers";

    export default {
        name: "ProductionWidget",
        mixins: [proxyValue],
        components: {TagsWidget, DatePicker, ConfirmationModal, TaskContent },
        computed: {
            tabs() {
                const result = this.proxyData.tasks.map((task, index) => ({
                    index,
                    status: task.status,
                    title: task.title.length > 15 ? task.title.substring(0, 15) + '...' : task.title,
                    enabled: true,
                    slug: task.departmentSlug
                }));
                const systemTasks = helpers.getDepartments().map(dpt => dpt.slug);
                // add separator
                const customIndex = result.findIndex(task => false === systemTasks.includes(task.slug))
                if (customIndex !== -1) {
                    result.splice(customIndex, 0, {
                        enabled: false
                    })
                }
                return result;
            },
            canEdit() {
                return this.$user.can(this.$privilages.CAN_PRODUCTION);
            }
        },
        methods: {
            getStatusStyle(statusId) {
                return helpers.getStatusStyle(statusId)
            },
            handleDelete(id) {
                this.proxyData.tasks = this.proxyData.tasks.filter(task => task.id !== id);
            },
        }
    }
</script>

<style lang="scss">
    .production-tab-panel {
        width: 25%;
        min-width: 200px;
    }

    .statusNotify {
        display: inline-block;
        width: 12px;
        height: 12px;
        margin-right: 5px;
        border: 1px solid #666;
        background-color: white;
        border-radius: 3px;
    }
</style>