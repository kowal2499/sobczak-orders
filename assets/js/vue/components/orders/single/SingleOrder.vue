<template>

    <div>

        <button
            @click.prevent="save"
            :disabled="locked"
            href="#" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm mb-3"
        >
            <i class="btn-i" :class="locked ? 'fa fa-spinner fa-spin': 'fa fa-floppy-o'"></i>
            Zapisz zmiany
        </button>

        <div class="row">
            <div class="col-md-8">
                <collapsible-card :title="'Produkcja'" :locked="locked" v-if="orderData.production.data.length !== 0">
                    <production-widget
                        v-model="orderData.production.data"
                        :departments="departments"
                    ></production-widget>
                </collapsible-card>

                <collapsible-card :title="'Wymagania'" :locked="locked" v-if="orderData.line">
                    <requirements-widget
                        v-model="orderData.line"
                    ></requirements-widget>
                </collapsible-card>

                <collapsible-card :title="'Dodatkowe zamówienia'" :locked="locked">
                    Jak mnie widać?
                </collapsible-card>
            </div>

            <div class="col-md-4">
                <collapsible-card :title="'Produkt'" :locked="locked" v-if="orderData.product">
                    <product-widget v-model="orderData.product"></product-widget>
                </collapsible-card>

                <collapsible-card :title="'Klient'" :locked="locked" v-if="orderData.customer">
                    <customer-widget v-model="orderData.customer"></customer-widget>
                </collapsible-card>
            </div>
        </div>

    </div>
</template>

<script>

    import CollapsibleCard from "../../base/CollapsibleCard";
    import ordersApi from "../../../api/neworder";
    import ProductionWidget from "./ProductionWidget";
    import RequirementsWidget from "./RequirementsWidget";
    import ProductWidget from "./ProductWidget";
    import CustomerWidget from "./CustomerWidget";

    export default {
        name: "SingleOrder",
        components: { CollapsibleCard, ProductionWidget, RequirementsWidget, ProductWidget, CustomerWidget },
        props: ['lineId'],

        data() {
            return {
                locked: false,
                orderData: {
                    production: {
                        data: []
                    },
                    customer: {}
                },
                departments: []
            }
        },

        methods: {
            save() {
                this.locked = true;

                ordersApi.updateOrder(this.lineId, this.orderData.production.data, this.orderData.line)
                    .then(({data}) => {
                        if (data && Array.isArray(data.newStatuses) && data.newStatuses.length > 0) {

                            data.newStatuses.forEach(newStatus => {
                                let production = this.orderData.production.data.find(prod => { return prod.id === newStatus.productionId; });
                                if (production) {
                                    production.statusLog.push({ createdAt: newStatus.createdAt, currentStatus: newStatus.currentStatus });
                                }
                            })
                        }

                        alert('Zmiany zapisane')
                    })
                    .catch(() => {})
                    .finally(() => { this.locked = false;})
            }
        },

        mounted() {
            this.locked = true;

            ordersApi.fetchAgreements({ agreementLineId: this.lineId })
                .then(({data}) => {
                    if (data && Array.isArray(data.orders) && data.orders.length === 1) {
                        this.orderData = data.orders[0];
                        this.departments = data.departments;
                    }
                })
                .catch(() => {})
                .finally(() => {
                    this.locked = false;
                })
        }
    }
</script>

<style scoped>

</style>