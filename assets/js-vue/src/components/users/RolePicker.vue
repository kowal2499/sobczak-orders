<template>
    <div>
        <div class="form-group" v-for="role in getRoles()">
            <label><input type="checkbox" :value="role.value" v-model="subject.roles" class="pl-4"><span class="pl-3">{{ role.name }}</span></label>
        </div>

        <div v-if="subject && Array.isArray(subject.roles) && subject.roles.indexOf('ROLE_CUSTOMER') !== -1">

            <div class="card">
                <div class="card-header">
                    Klienci do których użytkownik będzie miał dostęp
                </div>

                <div class="card-body">
                    <customer-select v-model="subject.customers2Users" />
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import helpers from "../../helpers";
    import CustomerSelect from "../base/CustomerSelect";

    export default {
        name: 'RolePicker',

        components: { CustomerSelect },

        props: [ 'value' ],

        data() {
            return {
                subject: this.value
            }
        },

        methods: {
            getRoles() {
                return helpers.roles;
            }
        }

    }
</script>

<style scoped>

</style>