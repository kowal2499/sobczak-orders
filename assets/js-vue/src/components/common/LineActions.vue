<template>
    <div>
        <dropdown class="icon-only">

            <template v-if="line && line.status === 25">
                <!-- 1.1 delete order line -->
                <a class="dropdown-item text-danger" href="#"
                   @click.prevent="isModalConfirmDeleteOrderVisible = true"
                   v-if="canDeleteOrder()"
                >
                    <i class="fa fa-exclamation-circle text-danger" aria-hidden="true"/> {{ $t('deleteOrder') }}
                </a>
                <!-- 1.2 restore order line -->
                <a class="dropdown-item" href="#"
                   @click.prevent="isModalConfirmRestoreOrderVisible = true"
                >
                    <i class="fa fa-undo" aria-hidden="true"/> {{ $t('restoreOrder') }}
                </a>
            </template>

            <template v-if="line && line.status !== 25">

                <!-- sekcja wspólna -->

                <a class="dropdown-item" :href="__mixin_getRouting('agreement_line_details') + '/' + line.id">
                    <i class="fa fa-tasks" aria-hidden="true"/> {{ $t('Panel') }}
                </a>

                <hr style="margin: 5px auto">

                <!-- 1. sekcja zamówienie -->

                <!-- 1.1 edit order -->
                <a class="dropdown-item" :href="__mixin_getRouting('orders_edit') + '/' + line.Agreement.id">
                    <i class="fa fa-pencil" aria-hidden="true"/> {{ $t('editOrder') }}
                </a>

                <!-- 1.1 set warehouse status -->
                <a class="dropdown-item" href="#"
                   @click.prevent="isModalConfirmWarehouseVisible = true"
                   v-if="canWarehouse()"
                >
                    <i class="fa fa-archive" aria-hidden="true"/> {{ $t('setWarehouseStatus') }}
                </a>

                <!-- 1.2 set archive status -->
                <a class="dropdown-item" href="#"
                   @click.prevent="isModalConfirmArchiveVisible = true"
                   v-if="canArchive()"
                >
                    <i class="fa fa-archive" aria-hidden="true"/> {{ $t('setArchivedStatus') }}
                </a>

                <!-- 1.3 set trash status -->
                <a class="dropdown-item text-danger" href="#"
                   @click.prevent="isModalConfirmTrashOrderVisible = true"
                   v-if="canTrashOrder()"
                >
                    <i class="fa fa-trash text-danger" aria-hidden="true"/> {{ $t('trashOrder') }}
                </a>

                <span v-if="canStartProduction(line)">
                    <hr style="margin: 5px auto">

                    <!-- sekcja produkcja -->

                    <a class="dropdown-item" href="#"
                       @click="startProduction(line)">
                        <i class="fa fa-play" aria-hidden="true"/> {{ $t('startProduction') }}
                    </a>
                </span>

            </template>
        </dropdown>

        <!-- Modal wyrzucania zamówienia do kosza -->

        <confirmation-modal
                :show="isModalConfirmTrashOrderVisible"
                :busy="isModalConfirmTrashOrderBusy"
                @answerYes="updateAgreementStatus(25)"
                @closeModal="isModalConfirmTrashOrderVisible = false"
                v-if="isModalConfirmTrashOrderVisible"
        >
            <div>
                <p><strong>{{ $t('areYouSureToTrashOrder') }} {{ line.Agreement.orderNumber }}'?</strong></p>

                <ul class="list-unstyled">
                    <li>{{ line.Product.name }}</li>
                </ul>

            </div>

        </confirmation-modal>

        <!-- Modal usuwania zamówienia (ustawia pole deleted w bazie) -->

        <confirmation-modal
                :show="isModalConfirmDeleteOrderVisible"
                :busy="isModalConfirmDeleteOrderBusy"
                @answerYes="deleteOrder()"
                @closeModal="isModalConfirmDeleteOrderVisible = false"
                v-if="isModalConfirmDeleteOrderVisible"
        >
            <div>
                <p><strong>{{ $t('areYouSureToDeleteOrder') }} {{ line.Agreement.orderNumber }}'?</strong></p>

                <ul class="list-unstyled">
                    <li>{{ line.Product.name }}</li>
                </ul>

                <div class="alert alert-danger" v-if="hasProduction(line)">
                    <i class="fa fa-exclamation-circle" aria-hidden="true"></i>
                    {{ $t('willAlsoDeleteProduction') }}
                </div>

            </div>

        </confirmation-modal>

        <!-- Modal przywracania zamówienia (wyciąga z kosza) -->

        <confirmation-modal
                :show="isModalConfirmRestoreOrderVisible"
                :busy="isModalConfirmRestoreOrderBusy"
                @answerYes="restoreOrder()"
                @closeModal="isModalConfirmRestoreOrderVisible = false"
                v-if="isModalConfirmRestoreOrderVisible"
        >
            <div>
                <p><strong>{{ $t('areYouSureToRestoreOrder') }} {{ line.Agreement.orderNumber }}'?</strong></p>
                <ul class="list-unstyled">
                    <li>{{ line.Product.name }}</li>
                </ul>
            </div>

        </confirmation-modal>

        <!-- Modal dla magazynowania zamówienia -->
        <confirmation-modal
                :show="isModalConfirmWarehouseVisible"
                @answerYes="updateAgreementStatus(15)"
                @closeModal="isModalConfirmWarehouseVisible = false"
                v-if="isModalConfirmWarehouseVisible"
        >
            <div>
                <p><strong>{{ $t('setAsWarehoused') }}</strong></p>
                <ul class="list-unstyled">
                    <li>{{ $t('id') }}: {{ line.Agreement.orderNumber}}</li>
                    <li>{{ $t('product') }}: {{ line.Product.name }}</li>
                    <li>{{ $t('customer') }}: {{ __mixin_customerName(line.Agreement.Customer) }}</li>
                </ul>
            </div>

        </confirmation-modal>

        <!-- Modal dla archiwizowania zamówienia -->
        <confirmation-modal
                :show="isModalConfirmArchiveVisible"
                @answerYes="updateAgreementStatus(20)"
                @closeModal="isModalConfirmArchiveVisible = false"
                v-if="isModalConfirmArchiveVisible"
        >
            <div>
                <p><strong>{{ $t('setAsArchived') }}</strong></p>
                <ul class="list-unstyled">
                    <li>{{ $t('id') }}: {{ line.Agreement.orderNumber}}</li>
                    <li>{{ $t('product') }}: {{ line.Product.name }}</li>
                    <li>{{ $t('customer') }}: {{ __mixin_customerName(line.Agreement.Customer) }}</li>
                </ul>
            </div>

        </confirmation-modal>

    </div>
</template>

<script>
    import Dropdown from '../base/Dropdown';
    import ConfirmationModal from "../base/ConfirmationModal";

    import ApiNewOrder from "../../api/neworder"
    import ApiProduction from "../../api/production";

    /**
     *  todo: usuwanie zamówienia powinno informować o usuwaniu wszystkich podpiętych produków i zleceń produkcyjnych.
     *  Teraz informacje są generowane w kontekście produktu/produkcji z bieżącej linii zamówienia
     */

    /**
     * todo: usunięcie jednej lub kilku pozycji z listy nie powinno odświeżać całości
     */


    export default {
        name: 'LineActions',
        components: { Dropdown, ConfirmationModal },
        props: {
            line: {
                type: Object,
                default: {}
            }
        },
        data() {
            return {
                isModalConfirmTrashOrderVisible: false,
                isModalConfirmTrashOrderBusy: false,

                isModalConfirmDeleteOrderVisible: false,
                isModalConfirmDeleteOrderBusy: false,

                isModalConfirmDeleteProductionVisible: false,
                isModalConfirmDeleteProductionBusy: false,

                isModalConfirmWarehouseVisible: false,
                isModalConfirmWarehouseBusy: false,

                isModalConfirmArchiveVisible: false,
                isModalConfirmArchiveBusy: false,

                isModalConfirmRestoreOrderVisible: false,
                isModalConfirmRestoreOrderBusy: false,
            }
        },
        methods: {

            hasProduction(line) {
                return line.productions && line.productions.length !== 0;
            },

            canStartProduction(line) {
                // można rozpocząć produkcję jeśli są uprawnienia i nie rozpoczęto jej wcześniej
                return (
                    this.hasProduction(line) === false &&
                    this.$user.can(this.$privilages.CAN_PRODUCTION)
                );
            },

            startProduction(line) {

                ApiNewOrder.startProduction(line.id)
                    .then(({data}) => {
                        line.productions = Array.isArray(data) ? data : [];
                        EventBus.$emit('message', {
                            type: 'success',
                            content: this.$t('addedToSchedule')
                        });
                        EventBus.$emit('statusUpdated');
                        this.$emit('lineChanged');
                    })
                    .finally(() => {})
            },

            canDeleteOrder() {
                return this.$user.can(this.$privilages.CAN_ORDERS_DELETE);
            },

            canTrashOrder() {
                return this.$user.can(this.$privilages.CAN_ORDERS_DELETE);
            },

            deleteOrder() {
                this.isModalConfirmDeleteOrderBusy = true;

                ApiNewOrder.deleteAgreementLine(this.line.id)
                    .then(() => {
                        EventBus.$emit('message', {
                            type: 'success',
                            content: this.$t('orderDeleted')
                        });
                        EventBus.$emit('statusUpdated');
                        this.$emit('lineChanged');
                    })
                    .finally(() => {
                        this.isModalConfirmDeleteOrderBusy = false;
                        this.isModalConfirmDeleteOrderVisible = false;
                    });
            },

            restoreOrder() {
                /** jeśli jest produkcja to ustaw status 'w realizacji'
                 *  w przeciwnym razie ustaw na oczekujące
                 */
                if (this.hasProduction(this.line)) {
                    this.updateAgreementStatus(10);
                } else {
                    this.updateAgreementStatus(5);
                }
            },

            // canDeleteProduction() {
            //     return this.hasProduction(this.line) && this.$user.can(this.$privilages.CAN_ORDERS_DELETE);
            // },
            //
            // deleteProduction() {
            //     this.isModalConfirmDeleteProductionBusy = true;
            //     ApiProduction.delete(this.line.line.id)
            //         .then(() => {
            //             Event.$emit('message', {
            //                 type: 'success',
            //                 content: this.$t('removedFromSchedule')
            //             });
            //             Event.$emit('statusUpdated');
            //             this.$emit('lineChanged');
            //         })
            //         .finally(() => {
            //             this.isModalConfirmDeleteProductionBusy = false;
            //             this.isModalConfirmDeleteProductionVisible = false;
            //         });
            // },

            canArchiveOrWarehouse() {
                let lastProductionStage = this.line.productions.find(stage => { return stage.departmentSlug === 'dpt05'; });
                return parseInt(lastProductionStage.status) === 3;
            },

            canWarehouse() {
                return this.hasProduction(this.line) &&
                    this.$user.can(this.$privilages.CAN_PRODUCTION) &&
                    this.line.status !== 15 &&
                    this.canArchiveOrWarehouse()
                ;
            },

            updateAgreementStatus(statusCode) {
                this.isModalConfirmWarehouseBusy = true;
                this.isModalConfirmArchiveBusy = true;
                ApiNewOrder.setAgreementStatus(this.line.id, statusCode)
                    .then(({data}) => {
                        EventBus.$emit('message', {
                            type: 'success',
                            content: this.$t('statusChangeSaved')
                        });
                        EventBus.$emit('statusUpdated');
                        this.$emit('lineChanged');
                    })
                    .finally(() => {
                        this.isModalConfirmWarehouseBusy = false;
                        this.isModalConfirmWarehouseVisible = false;

                        this.isModalConfirmArchiveBusy = false;
                        this.isModalConfirmArchiveVisible = false;

                        this.isModalConfirmDeleteOrderBusy = false;
                        this.isModalConfirmDeleteOrderVisible = false;

                        this.isModalConfirmTrashOrderBusy = false;
                        this.isModalConfirmTrashOrderVisible = false;

                        this.isModalConfirmRestoreOrderBusy = false;
                        this.isModalConfirmRestoreOrderVisible = false;
                    })
                ;
            },

            canArchive() {
                return this.hasProduction(this.line) &&
                    this.$user.can(this.$privilages.CAN_PRODUCTION) &&
                    this.line.status !== 20 &&
                    this.canArchiveOrWarehouse()
                ;
            }

        }
    }
</script>

<style scoped>

</style>