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
            <div class="col-md-8">
                <collapsible-card :title="$t('orders.production')" :locked="locked" v-if="orderData.productions && orderData.productions.length !== 0">
                    <production-widget
                            v-model="orderData.productions"
                            :task-types="['dpt01', 'dpt02', 'dpt03', 'dpt04', 'dpt05']"
                    />
                </collapsible-card>

                <collapsible-card :title="$t('orders.orderDetails')" :locked="locked">
                    <details-widget
                        v-model="orderData"
                        :statuses="statuses"
                    ></details-widget>
                </collapsible-card>

                <collapsible-card :title="$t('orders.additionalOrders')" :locked="locked" v-if="orderData.productions && orderData.productions.length !== 0 && canEditLine()">
                    <production-widget
                            v-model="orderData.productions"
                            :task-types="['custom_task']"
                            :can-add="true"
                    />
                </collapsible-card>

            </div>

            <div class="col-md-4">
                <collapsible-card :title="$t('product')" :locked="locked" v-if="orderData.Product">
                    <product-widget v-model="orderData.Product"/>
                </collapsible-card>

                <collapsible-card :title="$t('customer')" :locked="locked" v-if="orderData.Agreement && orderData.Agreement.Customer">
                    <customer-widget v-model="orderData.Agreement.Customer"/>
                </collapsible-card>

                <collapsible-card :title="$t('orders.attachments')" :locked="locked" v-if="orderData.Agreement">
                    <div class="row">
                        <div class="col-sm-12">
                            <attachments-widget :attachments="orderData.Agreement.attachments || []" :show-name="true"
                                                :tooltip="false"/>
                        </div>
                    </div>
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
    import CustomerWidget from "./CustomerWidget";
    import AttachmentsWidget from "./AttachmentsWidget";
    import _ from 'lodash';

    export default {
        name: "SingleOrder",
        components: { CollapsibleCard, ProductionWidget, DetailsWidget, ProductWidget, CustomerWidget, AttachmentsWidget },
        props: ['lineId', 'statuses'],

        data() {
            return {
                locked: false,
                orderData: {},
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
                    productions: this.prodToSave,
                })
                    .then(({data}) => {
                        if (data && Array.isArray(data.newStatuses) && data.newStatuses.length > 0) {

                            data.newStatuses.forEach(newStatus => {
                                let production = this.orderData.productions.find(prod => { return prod.id === newStatus.productionId; });
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

            canEditLine() {
                return this.$user.can(this.$privilages.CAN_PRODUCTION);
            }
        },

        mounted() {
            this.locked = true;

            ordersApi.fetchAgreements({ agreementLineId: this.lineId })
                .then(({data}) => {
                    if (data.data && Array.isArray(data.data) && data.data.length === 1) {
                        this.orderData = data.data[0];
                    }
                })
                .catch(() => {})
                .finally(() => {
                    this.locked = false;
                })
        },

        computed: {
            prodToSave() {
                let toSave = _.cloneDeep(this.orderData.productions);
                for (let prod of toSave) {
                    if (prod.statusLogs) {
                        for (let statusLog of prod.statusLogs) {
                            statusLog.user = statusLog.user ? statusLog.user.id : null;
                        }
                    }
                }
                return toSave;
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