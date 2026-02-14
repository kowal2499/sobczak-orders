<template>
    <div class="order-spec-list">
        <div>
            <div class="order-spec-list--item-title">{{ $t('deliveryDate') }}</div>
            <div>{{ agreementLine.confirmedDate | formatDate('YYYY-MM-DD') }}</div>
        </div>
    </div>
</template>

<script>
    export default {
        name: "AgreementLineWidget",

        props: {
            agreementLine: {
                type: Object,
                required: true
            },
            disableEdit: {
                type: Boolean,
                default: false
            }
        },
    }
</script>

<style scoped>
</style>