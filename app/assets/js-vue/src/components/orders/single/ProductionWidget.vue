<template>
    <div>
        <div v-if="hasGhost" class="ghost-banner mb-2">
            <i class="fa fa-clock-o mr-1" aria-hidden="true"></i>
            {{ $t('dashboard.ghostOrderBanner') }}
        </div>

        <b-tabs v-model="activeTabIndex" pills card vertical nav-wrapper-class="production-tab-panel">
            <b-tab
                v-for="tab in tabs"
                :key="tab.id"
                :disabled="false === tab.enabled"
            >
                <template #title>
                    <span v-if="tab.enabled">
                        <span class="statusNotify" :style="getStatusStyle(tab.status)"/> {{ tab.title }}
                    </span>
                    <hr v-else>
                </template>

                <task-content
                    v-model="proxyData.tasks[tasksIdToIndex[tab.id]]"
                    :can-edit="canEdit"
                    @delete="handleDelete"
                />
            </b-tab>
        </b-tabs>
        <hr>
        <TagsWidget module-name="agreement-line" v-model="proxyData.tags" :logs="proxyData.tagsData"></TagsWidget>
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
        props: {
            activeDepartment: { type: String, default: null },
        },
        data() {
            return {
                activeTabIndex: 0,
            }
        },
        computed: {
            tabs() {
                const systemTasks = [];
                const customTasks = [];

                const grantedDpts = helpers.getDepartments()
                    .filter(({grant}) => this.$user.can(grant))
                    .map(({slug}) => slug)

                const forbiddenDpts = helpers.getDepartments()
                    .filter(({grant}) => !this.$user.can(grant))
                    .map(({slug}) => slug)

                this.proxyData.tasks.forEach(task => {
                    const taskTitle = task.title || '';
                    let normalizedTask = {
                        id: task.id,
                        status: task.status,
                        title: taskTitle.length > 15 ? taskTitle.substring(0, 15) + '...' : taskTitle,
                        enabled: true,
                        slug: task.departmentSlug
                    }

                    if (forbiddenDpts.includes(task.departmentSlug)) {
                        return
                    }

                    if (grantedDpts.includes(normalizedTask.slug)) {
                        systemTasks.push(normalizedTask)
                    }
                })

                return systemTasks
            },
            canEdit() {
                return this.$user.can(this.$privilages.CAN_PRODUCTION);
            },
            tasksIdToIndex() {
                const byId = {}
                if (this.proxyData.tasks) {
                    this.proxyData.tasks.forEach((task, index) => {
                        byId[task.id] = index
                    })
                }
                return byId
            },
            hasGhost() {
                return this.proxyData.tasks.some(task => task.isGhost)
            }
        },
        watch: {
            tabs: {
                immediate: true,
                handler() {
                    this.applyActiveDepartment()
                }
            },
            activeDepartment() {
                this.applyActiveDepartment()
            }
        },
        methods: {
            getStatusStyle(statusId) {
                return helpers.getStatusStyle(statusId)
            },
            handleDelete(id) {
                this.proxyData.tasks = this.proxyData.tasks.filter(task => task.id !== id);
            },
            applyActiveDepartment() {
                if (!this.activeDepartment) {
                    return
                }
                const index = this.tabs.findIndex(tab => tab.slug === this.activeDepartment)
                if (index >= 0) {
                    this.activeTabIndex = index
                }
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