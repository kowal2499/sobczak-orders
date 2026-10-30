<template>
    <div>
        <SectionBlockTitle block :title="title" :breadcrumbs="breadcrumbs" />

        <AgreementForm :agreement-id="agreementId" class="section-gap" />
    </div>
</template>

<script>
import AgreementForm from '@/modules/agreement/view/AgreementForm.vue'

export default {
    name: 'OrderFormPage',
    components: { AgreementForm },
    props: {
        agreementId: { type: Number, default: 0 },
    },
    computed: {
        title() {
            return this.$t(this.agreementId ? 'app.title.orderEdit' : 'app.title.orderNew')
        },
        breadcrumbs() {
            return [
                { icon: 'home', href: '/', label: this.$t('dashboard.title') },
                { href: '/orders', label: this.$t('orders.list') },
                { label: this.title },
            ]
        },
    },
}
</script>

<style scoped>
.section-gap {
    margin-top: 2rem;
}
</style>
