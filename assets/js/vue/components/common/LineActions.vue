<template>
    <div>
        <dropdown class="icon-only">
            <template v-if="line">

                <!-- sekcja wspólna -->

                <a class="dropdown-item" :href="__mixin_getRouting('agreement_line_details') + '/' + line.line.id">
                    <i class="fa fa-tasks" aria-hidden="true"></i> {{ $t('Panel') }}
                </a>

                <hr style="margin: 5px auto">

                <!-- sekcja zamówienie -->

                <a class="dropdown-item" :href="__mixin_getRouting('orders_edit') + '/' + line.header.id">
                    <i class="fa fa-pencil" aria-hidden="true"></i> {{ $t('editOrder') }}
                </a>

                <a class="dropdown-item" href="#"
                   @click.prevent="isModalConfirmWarehouseVisible = true"
                   v-if="canWarehouse()"
                >
                    <i class="fa fa-archive" aria-hidden="true"></i> {{ $t('setWarehouseStatus') }}
                </a>

                <a class="dropdown-item" href="#"
                   @click.prevent="isModalConfirmArchiveVisible = true"
                   v-if="canArchive()"
                >
                    <i class="fa fa-archive" aria-hidden="true"></i> {{ $t('setArchivedStatus') }}
                </a>

<!--                todo: zamienić na funkcję kosza -->
<!--                <a class="dropdown-item text-danger" href="#"-->
<!--                   @click.prevent="isModalConfirmDeleteOrderVisible = true"-->
<!--                   v-if="canDeleteOrder(line)"-->
<!--                >-->
<!--                    <i class="fa fa-trash text-danger" aria-hidden="true"></i> {{ $t('deleteOrder') }}-->
<!--                </a>-->


                <hr style="margin: 5px auto">

                <!-- sekcja produkcja -->

                <a class="dropdown-item" href="#"
                   v-if="canStartProduction(line)"
                   @click="startProduction(line)"
                >
                    <i class="fa fa-play" aria-hidden="true"></i> {{ $t('startProduction') }}
                </a>

                <a class="dropdown-item" href="#"
                   @click.prevent="isModalConfirmDeleteProductionVisible = true"
                   v-if="canDeleteProduction()"
                >
                    <i class="fa fa-trash text-danger" aria-hidden="true"></i> <span class="text-danger">{{ $t('deleteProduction') }}</span>
                </a>

            </template>
        </dropdown>


        <!-- Modal usuwania zamówienia -->

        <confirmation-modal
                :show="isModalConfirmDeleteOrderVisible"
                :busy="isModalConfirmDeleteOrderBusy"
                @answerYes="deleteOrder(line)"
                @closeModal="isModalConfirmDeleteOrderVisible = false"
                v-if="isModalConfirmDeleteOrderVisible"
        >
            <div>
                <p><strong>{{ $t('areYouSureToDeleteOrder') }} {{ line.header.orderNumber }}'?</strong></p>

                <ul class="list-unstyled">
                    <li>{{ line.product.name }}</li>
                </ul>

                <div class="alert alert-danger" v-if="hasProduction(line)">
                    <i class="fa fa-exclamation-circle" aria-hidden="true"></i>
                    {{ $t('willAlsoDeleteProduction') }}
                </div>

            </div>

        </confirmation-modal>

        <!-- Modal usuwania produkcji -->

        <confirmation-modal
                :show="isModalConfirmDeleteProductionVisible"
                @answerYes="deleteProduction()"
                @closeModal="isModalConfirmDeleteProductionVisible = false"
                v-if="isModalConfirmDeleteProductionVisible"
        >
            <div>
                <p><strong>{{ $t('areYouSureToDeleteProduction') }}</strong></p>

                <ul class="list-unstyled">
                    <li>{{ $t('id') }}: {{ line.header.orderNumber}}</li>
                    <li>{{ $t('product') }}: {{ line.product.name }}</li>
                    <li>{{ $t('customer') }}: {{ __mixin_customerName(line.customer) }}</li>
                </ul>

                <div class="alert alert-danger">
                    <i class="fa fa-exclamation-circle" aria-hidden="true"></i>
                    {{ $t('willRemoveProductionData') }}
                </div>

                <div class="alert alert-info">
                    {{ $t('deleteNotification') }}
                </div>
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
                    <li>{{ $t('id') }}: {{ line.header.orderNumber}}</li>
                    <li>{{ $t('product') }}: {{ line.product.name }}</li>
                    <li>{{ $t('customer') }}: {{ __mixin_customerName(line.customer) }}</li>
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
                    <li>{{ $t('id') }}: {{ line.header.orderNumber}}</li>
                    <li>{{ $t('product') }}: {{ line.product.name }}</li>
                    <li>{{ $t('customer') }}: {{ __mixin_customerName(line.customer) }}</li>
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
                isModalConfirmDeleteOrderVisible: false,
                isModalConfirmDeleteOrderBusy: false,

                isModalConfirmDeleteProductionVisible: false,
                isModalConfirmDeleteProductionBusy: false,

                isModalConfirmWarehouseVisible: false,
                isModalConfirmWarehouseBusy: false,

                isModalConfirmArchiveVisible: false,
                isModalConfirmArchiveBusy: false,
            }
        },
        methods: {

            hasProduction(line) {
                return line.production && line.production.data.length !== 0;
            },

            canStartProduction(line) {
                // można rozpocząć produkcję jeśli są uprawnienia i nie rozpoczęto jej wcześniej
                return (
                    this.hasProduction(line) === false &&
                    this.$user.can(this.$privilages.CAN_PRODUCTION)
                );
            },

            startProduction(line) {
                let production = this.$helpers.getDepartments().map(department => {
                    return {
                        slug: department.slug,
                        name: department.name,
                        status: 0,
                        dateFrom: null,
                        dateTo: null
                    };
                });

                ApiNewOrder.storeProductionPlan(production, line.line.id)
                    .then(({data}) => {
                        line.production.data = Array.isArray(data) ? data[0] : [];
                        Event.$emit('message', {
                            type: 'success',
                            content: this.$t('addedToSchedule')
                        });
                        Event.$emit('statusUpdated');
                        this.$emit('lineChanged');
                    })
                    .finally(() => {})
            },

            canDeleteOrder(line) {
                return this.$user.can(this.$privilages.CAN_ORDERS_DELETE);
            },

            deleteOrder(line) {
                this.isModalConfirmDeleteOrderBusy = true;

                ApiNewOrder.deleteOrder(line.header.id)
                    .then(() => {
                        Event.$emit('message', {
                            type: 'success',
                            content: this.$t('orderDeleted')
                        });
                        Event.$emit('statusUpdated');
                        this.$emit('lineChanged');
                    })
                    .finally(() => {
                        this.isModalConfirmDeleteOrderBusy = false;
                        this.isModalConfirmDeleteOrderVisible = false;
                    });
            },

            canDeleteProduction() {
                return this.hasProduction(this.line) && this.$user.can(this.$privilages.CAN_ORDERS_DELETE);
            },

            deleteProduction() {
                this.isModalConfirmDeleteProductionBusy = true;
                ApiProduction.delete(this.line.line.id)
                    .then(() => {
                        Event.$emit('message', {
                            type: 'success',
                            content: this.$t('removedFromSchedule')
                        });
                        Event.$emit('statusUpdated');
                        this.$emit('lineChanged');
                    })
                    .finally(() => {
                        this.isModalConfirmDeleteProductionBusy = false;
                        this.isModalConfirmDeleteProductionVisible = false;
                    });
            },

            canArchiveOrWarehouse() {
                let lastProductionStage = this.line.production.data.find(stage => { return stage.departmentSlug === 'dpt05'; });
                return lastProductionStage.status === 3;
            },

            canWarehouse() {
                return this.hasProduction(this.line) &&
                    this.$user.can(this.$privilages.CAN_PRODUCTION) &&
                    this.line.line.status !== 15 &&
                    this.canArchiveOrWarehouse()
                ;
            },

            updateAgreementStatus(statusCode) {
                this.isModalConfirmWarehouseBusy = true;
                this.isModalConfirmArchiveBusy = true;
                ApiNewOrder.setAgreementStatus(this.line.line.id, statusCode)
                    .then(({data}) => {
                        Event.$emit('message', {
                            type: 'success',
                            content: this.$t('statusChangeSaved')
                        });
                        Event.$emit('statusUpdated');
                        this.$emit('lineChanged');
                    })
                    .finally(() => {
                        this.isModalConfirmWarehouseBusy = false;
                        this.isModalConfirmWarehouseVisible = false;

                        this.isModalConfirmArchiveBusy = false;
                        this.isModalConfirmArchiveVisible = false;
                    })
                ;
            },

            canArchive() {
                return this.hasProduction(this.line) &&
                    this.$user.can(this.$privilages.CAN_PRODUCTION) &&
                    this.line.line.status !== 20 &&
                    this.canArchiveOrWarehouse()
                    ;
            }

        }
    }
</script>

<style scoped>

</style>