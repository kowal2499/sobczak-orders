<template>

    <div class="d-flex flex-row align-items-start">
        <div>
            <div v-if="customer.name">
                {{ customer.name }}
            </div>
            <div v-if="customer.first_name || customer.last_name" class="small">
                {{ [customer.first_name, customer.last_name].filter(Boolean).join(' ') }}
            </div>
            <div v-if="customer.street || customer.city">
                <div>{{ [customer.street, customer.street_number, customer.apartment_number].filter(Boolean).join(' ') }}</div>
                <div>{{ [customer.country, customer.postal_code, customer.city].filter(Boolean).join(' ') }}</div>
            </div>
            <div v-if="customer.email">
                <i class="fa fa-paper-plane mr-1"></i><a :href="`mailto:${customer.email}`">{{ customer.email }}</a>
            </div>
            <div v-if="customer.phone">
                <i class="fa fa-phone-square mr-1"></i><a :href="`tel:${customer.phone}`">{{ customer.phone }}</a>
            </div>
        </div>
        <a :href="getEditLink" class="btn btn-sm btn-secondary shadow-sm ml-auto" v-if="userCan()">
            <i class="fa fa-pencil" aria-hidden="true"></i>
        </a>
    </div>
</template>

<script>
    import routing from "../../../api/routing";

    export default {
        name: "CustomerDetails",
        props: {
            customer: {
                type: Object,
                required: true
            }
        },

        computed: {
            getEditLink() {
                return routing.get('customers_edit').concat('/' + String(this.customer.id));
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