<template>
    <div>
        <button
            @click.prevent="save"
            :disabled="locked"
            href="#" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm mb-3"
            v-if="canEditLine()"
        >
            <i :class="locked ? 'fa fa-spinner fa-spin': 'fa fa-floppy-o'"/>
            <span class="pl-1">{{ $t('orders.saveChanges') }}</span>
        </button>

        <div class="row">
            <div class="col-12 col-lg-8">
                <collapsible-card :title="$t('orders.production')" :locked="locked" v-if="orderData.productions.tasks && orderData.productions.tasks.length !== 0">
                    <template #header v-if="canEditLine()">
                        <b-button size="sm" variant="success" :disabled="false === canAddNewTask || locked" @click="addCustomTask()">
                            {{ $t('orders.newTask') }}
                        </b-button>
                    </template>
                    <production-widget v-model="orderData.productions"/>
                </collapsible-card>

                <collapsible-card :title="$t('orders.orderProcessing')" :locked="locked">
                    <details-widget
                        v-model="orderData"
                        :statuses="statuses"
                    ></details-widget>
                </collapsible-card>
            </div>

            <div class="col-12 col-lg-4">
                <collapsible-card :title="$t('product')" :locked="locked" v-if="orderData.Product">
                    <product-widget :product="orderData.Product"/>
                </collapsible-card>

                <collapsible-card :title="$t('orders.orderDetails')" :locked="locked" v-if="orderData.Agreement">
                    <agreement-widget :agreement="orderData.Agreement" />
                </collapsible-card>

                <collapsible-card :title="$t('orders.attachments')" :locked="locked" v-if="orderData.Agreement">
                    <attachments-widget
                        :attachments="orderData.Agreement.attachments || []"
                        :show-name="true"
                        :tooltip="false"
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

    export default {
        name: "SingleOrder",
        components: { CollapsibleCard, ProductionWidget, DetailsWidget, ProductWidget, AttachmentsWidget, AgreementWidget },
        props: ['lineId', 'statuses'],

        data() {
            return {
                locked: false,
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
                    Agreement: {}
                },
            }
        },

        methods: {
            save() {
                this.locked = true;
                ordersApi.updateOrder(this.lineId, {
                    status: this.orderData.status,
                    confirmedDate: this.orderData.confirmedDate,
                    description: this.orderData.description,
                    factor: this.orderData.factor,
                    productions: this.orderData.productions.tasks.map(task => ({
                        ...task,
                        statusLogs: task.statusLogs.map(log => ({...log, user: (log.user || {id: null}).id}))
                    })),
                    tags: this.orderData.productions.tags
                })
                    .then(({data}) => {
                        if (data && Array.isArray(data.newStatuses) && data.newStatuses.length > 0) {

                            data.newStatuses.forEach(newStatus => {
                                let production = this.orderData.productions.tasks.find(prod => { return prod.id === newStatus.productionId; });
                                if (production && production.statusLogs) {
                                    production.statusLogs.push({ createdAt: newStatus.createdAt, currentStatus: newStatus.currentStatus });
                                }
                            })
                        }

                        EventBus.$emit('message', {
                            type: 'success',
                            content: this.$t('orders.changesWereSaved')
                        });

                        EventBus.$emit('statusUpdated');
                    })
                    .catch((data) => {
                        for (let msg of data.response.data) {
                            EventBus.$emit('message', {
                                type: 'error',
                                content: msg
                            });
                        }
                    })
                    .finally(() => { this.locked = false;})
            },

            addCustomTask() {
                this.orderData.productions.tasks.push({
                    dateStart: null,
                    dateEnd: null,
                    departmentSlug: 'custom_task',
                    description: null,
                    id: null,
                    title: this.$t('orders.newTask'),
                    status: "10",
                    statusLogs: [{
                            id: null,
                            currentStatus: "10",
                            createdAt: (new moment()).format('YYYY-MM-DD HH:mm:ss'),
                            user: {
                                id: this.$user.getId(),
                                userFullName: this.$user.getName(),
                            }
                    }],
                });
            },

            canEditLine() {
                return this.$user.can(this.$privilages.CAN_PRODUCTION);
            }
        },

        mounted() {
            this.locked = true;

            ordersApi.fetchAgreements({ agreementLineId: this.lineId })
                .then(({data}) => {
                    if (data.data && Array.isArray(data.data) && data.data.length === 1) {
                        const src = data.data[0];
                        this.orderData = {
                            confirmedDate: src.confirmedDate,
                            factor: src.factor,
                            status: src.status,
                            Product: src.Product,
                            Agreement: src.Agreement,
                            description: src.description,
                            productions: {
                                tasks: src.productions, 
                                tags: src.tags.map(tag => tag.tagDefinition.id),
                                tagsData: src.tags.map(tag => ({
                                  definitionId: tag.tagDefinition.id,
                                  createdAt: tag.createdAt,
                                  userName: tag.user.userFullName
                                }))
                            }
                        }
                    }
                })
                .catch(() => {})
                .finally(() => {
                    this.locked = false;
                })
        },

        computed: {
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