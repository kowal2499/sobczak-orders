<template>
    <div>
        <div class="table-responsive">
            <table class="table table-bordered">
                <tr v-if="value.name">
                    <th>{{ $t('name') }}</th>
                    <td>{{ value.name }}</td>
                </tr>
                <tr v-if="value.factor && userCanProduction()" >
                    <th>{{ $t('orders.factor') }}</th>
                    <td>{{ value.factor }}</td>
                </tr>
                <tr v-if="value.description">
                    <th>{{ $t('orders.description') }}</th>
                    <td>{{ value.description }}</td>
                </tr>
            </table>
        </div>

        <a :href="getEditLink" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm mt-1 mb-1" v-if="userCan()">
            <i class="fa fa-pencil" aria-hidden="true"></i>
            {{ $t('orders.edit') }}
        </a>
    </div>
</template>

<script>

    import routing from "../../../api/routing";

    export default {
        name: "ProductWidget",
        props: ['value'],

        computed: {
            getEditLink() {
                return routing.get('products_edit').concat('/' + String(this.value.id));
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