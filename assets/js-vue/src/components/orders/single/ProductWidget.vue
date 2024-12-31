<template>
    <div class="d-flex flex-row align-items-start">
        <div class="order-spec-list">
            <div v-if="product.name">
                <div class="order-spec-list--item-title">{{ $t('name') }}</div>
                <div>{{ product.name }}</div>
            </div>
            <div v-if="product.factor && userCanProduction()" >
                <div class="order-spec-list--item-title">{{ $t('orders.factor') }}</div>
                <div>{{ product.factor }}</div>
            </div>
            <div v-if="product.description">
                <div class="order-spec-list--item-title">{{ $t('orders.description') }}</div>
                <div>{{ product.description }}</div>
            </div>
        </div>
        <a :href="editLink" class="btn btn-sm btn-secondary shadow-sm ml-auto" v-if="userCan()">
            <i class="fa fa-pencil" aria-hidden="true"></i>
        </a>
    </div>
</template>

<script>

    import routing from "../../../api/routing";

    export default {
        name: "ProductWidget",
        props: {
            product: {
                type: Object,
                required: true
            }
        },

        computed: {
            editLink() {
                return routing.get('products_edit').concat('/' + String(this.product.id));
            }
        },

        methods: {
            userCan() {
                return this.$user.can(this.$privilages.CAN_PRODUCTS);
            },

            userCanProduction() {
                return this.$user.can(this.$privilages.CAN_PRODUCTION);
            }
        }

    }
</script>

<style scoped>

</style>