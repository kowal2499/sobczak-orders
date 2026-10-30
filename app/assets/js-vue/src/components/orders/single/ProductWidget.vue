<template>
    <div class="d-flex flex-row align-items-start">
        <div class="order-spec-list">
            <div v-if="product.name">
                <div class="order-spec-list--item-title">{{ $t('name') }}</div>
                <div>{{ product.name }}</div>
            </div>
<!--            TODO: Powinniśmy zwracać factor z zamówienia a nie z produktu -->
<!--            <div v-if="product.factor && userCanProduction()" >-->
<!--                <div class="order-spec-list&#45;&#45;item-title">{{ $t('orders.factor') }}</div>-->
<!--                <div>{{ product.factor }}</div>-->
<!--            </div>-->
            <div v-if="product.description">
                <div class="order-spec-list--item-title">{{ $t('orders.description') }}</div>
                <div>{{ product.description }}</div>
            </div>
        </div>
        <router-link :to="{ name: 'product-edit', params: { id: product.id } }" class="btn btn-sm btn-outline-primary ml-auto" v-if="userCan()">
            <i class="fa fa-pencil" aria-hidden="true"></i>
        </router-link>
    </div>
</template>

<script>
    export default {
        name: "ProductWidget",
        props: {
            product: {
                type: Object,
                required: true
            },
            disableEdit: {
                type: Boolean,
                default: false
            }
        },


        methods: {
            userCan() {
                return !this.disableEdit && this.$user.can(this.$privilages.CAN_PRODUCTS);
            },

            userCanProduction() {
                return !this.disableEdit && this.$user.can(this.$privilages.CAN_PRODUCTION);
            }
        }

    }
</script>

<style scoped>

</style>