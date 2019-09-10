<template>
    <div>
        <table class="table table-bordered">

            <tr v-if="value.name">
                <th>Nazwa</th>
                <td>{{ value.name }}</td>
            </tr>
            <tr v-if="value.first_name || value.last_name">
                <th>Imię i nazwisko</th>
                <td>{{ value.first_name }} {{ value.last_name }}</td>
            </tr>
            <tr v-if="value.street || value.city">
                <th>Adres</th>
                <td>
                    <div>{{ value.street }} {{ value.street_number }} {{ value.apartment_number }}</div>
                    <div>{{ value.postal_code}} {{ value.city }}</div>
                    <div>{{ value.country }}</div>
                </td>
            </tr>

            <tr v-if="value.email">
                <th>E-mail</th>
                <td><a :href="'mailto:'.concat(value.email)">{{ value.email }}</a></td>
            </tr>

            <tr v-if="value.phone">
                <th>Telefon</th>
                <td>{{ value.phone }}</td>
            </tr>

        </table>
        <a :href="getEditLink" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm mb-3" v-if="userCan()">
            <i class="fa fa-pencil" aria-hidden="true"></i>
            Edytuj
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