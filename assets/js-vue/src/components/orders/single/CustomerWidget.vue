<template>
    <div>
        <div class="table-responsive">
            <table class="table table-bordered">

                <tr v-if="value.name">
                    <th>{{ $t('name') }}</th>
                    <td>{{ value.name }}</td>
                </tr>
                <tr v-if="value.first_name || value.last_name">
                    <th>{{ $t('orders.firstLastName') }}</th>
                    <td>{{ value.first_name }} {{ value.last_name }}</td>
                </tr>
                <tr v-if="value.street || value.city">
                    <th>{{ $t('address') }}</th>
                    <td>
                        <div>{{ value.street }} {{ value.street_number }} {{ value.apartment_number }}</div>
                        <div>{{ value.postal_code}} {{ value.city }}</div>
                        <div>{{ value.country }}</div>
                    </td>
                </tr>

                <tr v-if="value.email">
                    <th>{{ $t('orders.email') }}</th>
                    <td><a :href="'mailto:'.concat(value.email)">{{ value.email }}</a></td>
                </tr>

                <tr v-if="value.phone">
                    <th>{{ $t('orders.phone') }}</th>
                    <td>{{ value.phone }}</td>
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
        name: "CustomerWidget",
        props: ['value'],

        computed: {
            getEditLink() {
                return routing.get('customers_edit').concat('/' + String(this.value.id));
            }
        },

        methods: {
            userCan() {
                return this.$user.can(this.$privilages.CAN_CUSTOMERS) || this.$user.can(this.$privilages.CAN_CUSTOMERS_OWNED_ONLY);
            }
        }

    }
</script>

<style scoped>

</style>