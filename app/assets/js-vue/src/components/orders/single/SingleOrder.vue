<template>
    <div>
        <button
            @click.prevent="save"
            :disabled="locked || isSaving"
            href="#" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm mb-3"
            v-if="canEditLine()"
        >
            <i :class="isSaving ? 'fa fa-spinner fa-spin': 'fa fa-floppy-o'"/>
            <span class="pl-1">{{ $t('orders.saveChanges') }}</span>
        </button>

        <div class="row">
            <div class="col-12 col-lg-8">
                <collapsible-card :title="$t('orders.production')" :locked="locked" v-if="orderData.productions.tasks && orderData.productions.tasks.length !== 0">
                    <production-widget v-model="orderData.productions"/>
                </collapsible-card>

                <collapsible-card :title="$t('orders.orderProcessing')" :locked="locked">
                    <details-widget
                        v-model="orderData"
                        :taskStatuses="taskStatuses"
                        :hasGhost="hasGhost"
                    ></details-widget>
                </collapsible-card>

                <collapsible-card title="Zadania" :locked="locked" v-if="$user.can('task.orphans:read')">
                    <tasks-view ref="tasksView" :agreementLineId="lineId" :show-save-button="false" />
                </collapsible-card>
            </div>

            <div class="col-12 col-lg-4">
                <collapsible-card :title="$t('product')" :locked="locked" v-if="orderData.Product">
                    <product-widget :product="orderData.Product"/>
                </collapsible-card>

                <collapsible-card :title="$t('orders.orderDetails')" :locked="locked" v-if="orderData.Agreement">
                    <agreement-widget :agreement="orderData.Agreement" />
                </collapsible-card>

                <collapsible-card :title="$t('agreement_line_list.factorsForm.sidebarTitle')" :locked="locked" v-if="canManageFactors">
                    <Sidebar
                        :title="$t('agreement_line_list.factorsForm.sidebarTitle')"
                        sidebar-class="size-100 size-lg-75"
                    >
                        <template #sidebar-action="{ open }">
                            <button class="btn btn-outline-primary btn-sm" @click="open">
                                {{ $t('agreement_line_list.factorsForm.manageFactorsButton') }}
                            </button>
                        </template>
                        <template #sidebar-content="{ close }">
                            <FactorsView
                                :agreement-line="orderData"
                                :agreement-line-id="orderData.id"
                                @close="close"
                            />
                        </template>
                    </Sidebar>
                </collapsible-card>

                <collapsible-card :title="$t('orders.attachments')" :locked="locked" v-if="orderData.Agreement">
                    <attachments-widget
                        :attachments="orderData.Agreement.attachments || []"
                        :show-name="true"
                        :tooltip="false"
                    />
                </collapsible-card>

                <collapsible-card
                    :title="$t('agreement.activityLog.sectionTitle')"
                    :locked="locked"
                >
                    <ActivityLogList
                        ref="activityLog"
                        :fetcher="activityLogFetcher"
                        :load-on-mount="true"
                        compact
                    />
                </collapsible-card>
            </div>
        </div>
    </div>
</template>

<script>
    import CollapsibleCard from "../../base/CollapsibleCard";
    import ordersApi from "../../../api/neworder";
    import ProductionWidget from "./ProductionWidget";
    import DetailsWidget from "./DetailsWidget";
    import ProductWidget from "./ProductWidget";
    import AttachmentsWidget from "./AttachmentsWidget";
    import AgreementWidget from "./AgreementWidget";
    import _ from 'lodash';
    import moment from "moment";
    import Sidebar from '@/components/base/Sidebar.vue'
    import FactorsView from '@/modules/agreementLineList/view/FactorsView'
    import TasksView from '@/modules/task/view/TasksView'
    import ActivityLogList from '@/modules/agreement/components/ActivityLogList.vue'
    import { fetchActivityLogsForAgreementLine } from '@/modules/agreement/repository/activityLogRepository'

    export default {
        name: "SingleOrder",
        components: {
            CollapsibleCard, ProductionWidget, DetailsWidget, ProductWidget, AttachmentsWidget, AgreementWidget,
            Sidebar, FactorsView, TasksView, ActivityLogList,
        },
        props: ['lineId', 'taskStatuses'],

        data() {
            return {
                locked: false,
                isSaving: false,
                orderData: {
                    confirmedDate: '',
                    description: '',
                    factor: 0,
                    status: 0,
                    productions: {
                        tasks: [],
                        tags: []
                    },
                    Product: {},
                    Agreement: {},
                    tasks: [],
                },
                savedSnapshot: null,
            }
        },

        methods: {
            async save() {
                this.locked = true;
                this.isSaving = true;
                try {
                    if (this.$refs.tasksView && !await this.$refs.tasksView.validate()) {
                        this.$flash.warning(this.$t('orders.taskValidationFailed'));
                        return;
                    }

                    const { data } = await ordersApi.updateOrder(this.lineId, {
                        status: this.orderData.status,
                        confirmedDate: this.orderData.confirmedDate,
                        description: this.orderData.description,
                        factor: this.orderData.factor,
                        productions: this.orderData.productions.tasks
                            .filter(task => task.departmentSlug !== 'custom_task')
                            .map(task => ({
                                ...task,
                                statusLogs: task.statusLogs.map(log => ({...log, user: (log.user || {id: null}).id}))
                            })),
                        tags: this.orderData.productions.tags
                    });

                    if (data && Array.isArray(data.newStatuses) && data.newStatuses.length > 0) {
                        data.newStatuses.forEach(newStatus => {
                            let production = this.orderData.productions.tasks.find(prod => { return prod.id === newStatus.productionId; });
                            if (production && production.statusLogs) {
                                production.statusLogs.push({ createdAt: newStatus.createdAt, currentStatus: newStatus.currentStatus });
                            }
                        });
                    }

                    if (this.$refs.tasksView) {
                        await this.$refs.tasksView.save();
                    }

                    this.$refs.activityLog?.load();

                    this.snapshot();
                    this.$flash.success(this.$t('_saveSuccess'));
                    EventBus.$emit('statusUpdated');
                    this.$emit('saved');
                } catch (error) {
                    const data = error?.response?.data;
                    if (data?.errors?.title) {
                        this.$flash.danger(data.errors.title);
                    } else if (Array.isArray(data)) {
                        for (let msg of data) {
                            this.$flash.danger(msg);
                        }
                    }
                } finally {
                    this.locked = false;
                    this.isSaving = true;
                }
            },

            canEditLine() {
                return this.$user.can(this.$privilages.CAN_PRODUCTION);
            },

            snapshot() {
                this.savedSnapshot = _.cloneDeep(this.orderData);
            },

            revert() {
                if (this.savedSnapshot) {
                    this.orderData = _.cloneDeep(this.savedSnapshot);
                }
            },

            handleBeforeunload(event) {
                if (this.isDirty) {
                    event.preventDefault();
                    event.returnValue = '';
                    return '';
                }
            }
        },

        watch: {
            isDirty(val) {
                this.$emit('dirty-change', val);
            }
        },

        mounted() {
            window.addEventListener('beforeunload', this.handleBeforeunload);

            this.locked = true;

            ordersApi.fetchAgreements({ agreementLineId: this.lineId })
                .then(({data}) => {
                    if (data.data && Array.isArray(data.data) && data.data.length === 1) {
                        const src = data.data[0];
                        this.orderData = {
                            id: src.id,
                            confirmedDate: src.confirmedDate,
                            factor: src.factor,
                            status: src.status,
                            Product: src.Product,
                            Agreement: src.Agreement,
                            description: src.description,
                            productions: {
                                tasks: src.productions, 
                                tags: src.tags.map(tag => tag.tagDefinition.slug),
                                tagsData: src.tags.map(tag => ({
                                  slug: tag.tagDefinition.slug,
                                  createdAt: tag.createdAt,
                                  userName: tag.user.userFullName
                                }))
                            }
                        }
                        this.snapshot();
                    }
                })
                .catch(() => {})
                .finally(() => {
                    this.locked = false;
                })
        },

        beforeDestroy() {
            window.removeEventListener('beforeunload', this.handleBeforeunload);
        },

        computed: {
            isDirty() {
                if (!this.savedSnapshot) {
                    return false;
                }
                return !_.isEqual(this.orderData, this.savedSnapshot);
            },
            activityLogFetcher() {
                const lineId = this.lineId;
                return () => fetchActivityLogsForAgreementLine(lineId);
            },
            prodToSave() {
                let toSave = _.cloneDeep(this.orderData.productions.tasks);
                for (let prod of toSave) {
                    if (prod.statusLogs) {
                        for (let statusLog of prod.statusLogs) {
                            statusLog.user = statusLog.user ? statusLog.user.id : null;
                        }
                    }
                }
                return toSave;
            },
            canAddNewTask() {
                return this.orderData && false === this.orderData.productions.tasks.some(task => task.id === null)
            },
            canManageFactors() {
                return this.$user.can('production.factor_adjustment');
            },
            hasGhost() {
                return this.orderData.productions.tasks.some(task => task.isGhost)
            }
        }
    }
</script>

<style scoped lang="scss">
    button {
        i {
            padding: 0;
        }
    }
</style>