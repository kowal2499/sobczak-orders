<template>
    <div>
        <div class="role-options">
            <b-form-checkbox
                v-for="role in getRoles()"
                :key="role.value"
                v-model="subject.roles"
                :value="role.value"
            >
                {{ role.name }}
            </b-form-checkbox>
        </div>

        <div v-if="subject && Array.isArray(subject.roles) && subject.roles.indexOf('ROLE_CUSTOMER') !== -1" class="mt-3">
            <label class="form-label">{{ $t('user.form.customers') }}</label>
            <customer-select v-model="subject.customers2Users" />
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
.role-options {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem 1.5rem;
    min-height: calc(1.5em + 0.75rem + 2px);
    align-items: center;
}
</style>
