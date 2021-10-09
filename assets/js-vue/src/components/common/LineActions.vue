<template>
    <dropdown class="icon-only">
        <template v-if="isTrashed">
            <!-- 1.1 delete order line -->
            <confirmable-action
                :action-fn="deleteAction"
                :label="$t('deleteOrder')"
                icon-class="fa fa-exclamation-circle text-danger"
                v-if="canDelete"
            >
                <p><strong>{{ $t('areYouSureToDeleteOrder') }} {{ line.Agreement.orderNumber }}'?</strong></p>
                <ul class="list-unstyled">
                    <li>{{ line.Product.name }}</li>
                </ul>
                <div class="alert alert-danger" v-if="hasProduction">
                    <i class="fa fa-exclamation-circle" aria-hidden="true"></i>
                    {{ $t('willAlsoDeleteProduction') }}
                </div>
            </confirmable-action>

            <!-- 1.2 restore order line -->
            <confirmable-action
                :action-fn="restoreOrderAction"
                :label="$t('restoreOrder')"
                icon-class="fa fa-undo"
            >
                <p class="text-info">{{ $t('areYouSureToRestoreOrder') }} {{ line.Agreement.orderNumber }}'?</p>
                <ul class="list-unstyled">
                    <li>{{ line.Product.name }}</li>
                </ul>
            </confirmable-action>
        </template>

        <template v-if="false === isTrashed">
            <!-- sekcja wspólna -->
            <a class="dropdown-item" :href="`/agreement/line/${line.id}`">
                <i class="fa fa-tasks"/> {{ $t('_agreement_line_panel') }}
            </a>
            <hr style="margin: 5px auto">

            <!-- 1. sekcja zamówienie -->

            <!-- 1.1 edit order -->
            <a class="dropdown-item" :href="__mixin_getRouting('orders_edit') + '/' + line.Agreement.id">
                <i class="fa fa-pencil" aria-hidden="true"/> {{ $t('editOrder') }}
            </a>

            <!-- 1.1 set warehouse status -->
            <confirmable-action
                :action-fn="warehouseAction"
                :label="$t('setWarehouseStatus')"
                icon-class="fa fa-archive"
                v-if="canWarehouse"
            >
                <p class="text-info">{{ $t('setAsWarehoused') }}</p>
                <ul class="list-unstyled">
                    <li>{{ $t('id') }}: {{ line.Agreement.orderNumber }}</li>
                    <li>{{ $t('product') }}: {{ line.Product.name }}</li>
                    <li>{{ $t('customer') }}: {{ __mixin_customerName(line.Agreement.Customer) }}</li>
                </ul>
            </confirmable-action>

            <!-- 1.2 set archive status -->
            <confirmable-action
                :action-fn="archiveAction"
                :label="$t('setArchivedStatus')"
                icon-class="fa fa-archive"
                v-if="canArchive"
            >
                <p class="text-info">{{ $t('agreement_line_list.setAsArchived') }}</p>
                <ul class="list-unstyled">
                    <li>{{ $t('id') }}: {{ line.Agreement.orderNumber }}</li>
                    <li>{{ $t('product') }}: {{ line.Product.name }}</li>
                    <li>{{ $t('customer') }}: {{ __mixin_customerName(line.Agreement.Customer) }}</li>
                </ul>
            </confirmable-action>

            <!-- 1.3 set trash status -->
            <confirmable-action
                :action-fn="trashAction"
                :label="$t('agreement_line_list.trashOrder')"
                icon-class="fa fa-trash text-danger"
                anchor-class="text-danger"
            >
                <p class="text-info">
                    {{ $t('agreement_line_list.trashConfirmQuestion', {num: line.Agreement.orderNumber}) }}</p>
                <ul class="list-unstyled">
                    <li>{{ line.Product.name }}</li>
                </ul>
            </confirmable-action>

            <template v-if="canStartProduction">
                <hr style="margin: 5px auto">
                <!-- sekcja produkcja -->
                <a class="dropdown-item" href="#"
                   @click.prevent="startProduction()">
                    <i class="fa fa-play" aria-hidden="true"/> {{ $t('startProduction') }}
                </a>
            </template>
        </template>
    </dropdown>
</template>

<script>
import Dropdown from '../base/Dropdown';
import ApiNewOrder from "../../api/neworder"
import {
    AGREEMENT_LINE_STATUS_ARCHIVED,
    AGREEMENT_LINE_STATUS_DELETED, AGREEMENT_LINE_STATUS_MANUFACTURING, AGREEMENT_LINE_STATUS_WAITING,
    AGREEMENT_LINE_STATUS_WAREHOUSE
} from "../../definitions/agreementLineStatuses";
import {isValid} from "../../services/datesService";
import ConfirmableAction from "../../modules/agreementLineList/Actions/ConfirmableAction";

/**
 *  todo:
 *      -   usuwanie zamówienia powinno informować o usuwaniu wszystkich podpiętych produków i zleceń produkcyjnych.
 *          Teraz informacje są generowane w kontekście produktu/produkcji z bieżącej linii zamówienia
 *      -   usunięcie jednej lub kilku pozycji z listy nie powinno odświeżać całości
 */

export default {
    name: 'LineActions',
    components: {
        Dropdown,
        ConfirmableAction
    },
    props: {
        line: {
            type: Object,
            default: {}
        }
    },

    computed: {
        isTrashed() {
            return this.line && this.line.status === AGREEMENT_LINE_STATUS_DELETED
        },

        isProductionCompleted() {
            return isValid(this.line.productionCompletionDate)
        },

        canWarehouse() {
            return this.hasProduction &&
                this.$user.can(this.$privilages.CAN_PRODUCTION) &&
                this.line.status !== AGREEMENT_LINE_STATUS_WAREHOUSE &&
                this.isProductionCompleted
        },

        canArchive() {
            return this.hasProduction &&
                this.$user.can(this.$privilages.CAN_PRODUCTION) &&
                this.line.status !== AGREEMENT_LINE_STATUS_ARCHIVED &&
                this.isProductionCompleted
        },

        canTrash() {
            return this.$user.can(this.$privilages.CAN_ORDERS_DELETE);
        },

        canDelete() {
            return this.$user.can(this.$privilages.CAN_ORDERS_DELETE);
        },

        canStartProduction() {
            return this.hasProduction === false && this.$user.can(this.$privilages.CAN_PRODUCTION)
        },

        hasProduction() {
            return Array.isArray(this.line.productions) && this.line.productions.length !== 0;
        },
    },

    data: () => ({}),

    methods: {
        trashAction() {
            return this.updateAgreementStatus(AGREEMENT_LINE_STATUS_DELETED)
        },

        archiveAction() {
            return this.updateAgreementStatus(AGREEMENT_LINE_STATUS_ARCHIVED)
        },

        warehouseAction() {
            return this.updateAgreementStatus(AGREEMENT_LINE_STATUS_WAREHOUSE)
        },

        deleteAction() {
            return ApiNewOrder.deleteAgreementLine(this.line.id)
                .then(() => {
                    EventBus.$emit('message', {
                        type: 'success',
                        content: this.$t('orderDeleted')
                    });
                    EventBus.$emit('statusUpdated');
                    this.$emit('lineChanged');
                })
        },

        restoreOrderAction() {
            // jeśli jest produkcja to ustaw status 'w realizacji'
            //  w przeciwnym razie ustaw na oczekujące
            return this.hasProduction
                ? this.updateAgreementStatus(AGREEMENT_LINE_STATUS_MANUFACTURING)
                : this.updateAgreementStatus(AGREEMENT_LINE_STATUS_WAITING);
        },

        startProduction() {
            return ApiNewOrder.startProduction(this.line.id)
                .then(({data}) => {
                    this.line.productions = Array.isArray(data) ? data : [];
                    EventBus.$emit('message', {
                        type: 'success',
                        content: this.$t('addedToSchedule')
                    });
                    EventBus.$emit('statusUpdated');
                    this.$emit('lineChanged');
                })
        },

        updateAgreementStatus(statusCode) {
            return ApiNewOrder.setAgreementStatus(this.line.id, statusCode)
                .then(() => {
                    EventBus.$emit('message', {
                        type: 'success',
                        content: this.$t('statusChangeSaved')
                    });
                    EventBus.$emit('statusUpdated');
                    this.$emit('lineChanged');
                })
        },
    }
}
</script>

<style scoped>

</style>