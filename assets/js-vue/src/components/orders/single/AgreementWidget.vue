<template>
    <div class="order-spec-list">
        <div>
            <div class="order-spec-list--item-title">{{ $t('orderNumber') }}</div>
            <div>{{ agreement.orderNumber }}</div>
        </div>

        <div>
            <div class="order-spec-list--item-title">{{ $t('receiveDate') }}</div>
            <div>{{ agreement.createDate | formatDate('YYYY-MM-DD HH:mm') }}</div>
        </div>

        <div>
            <div class="order-spec-list--item-title">{{ $t('createdBy') }}</div>
            <div v-if="agreement.user">{{ agreement.user.userFullName }}</div>
            <div v-else class="text-muted"><i class="fa fa-ban mr-2"></i> {{ $t('noData') }}</div>
        </div>

        <div v-if="agreement.Customer">
            <div class="order-spec-list--item-title">{{ $t('customer') }}</div>
            <CustomerDetails :customer="agreement.Customer" />
        </div>
    </div>
</template>

<script>
    import CustomerDetails from "./CustomerDetails";

    export default {
        name: "AgreementWidget",

        props: {
            agreement: {
                type: Object,
                required: true
            }
        },

        components: {
            CustomerDetails
        },
    }
</script>

<style scoped>
</style>