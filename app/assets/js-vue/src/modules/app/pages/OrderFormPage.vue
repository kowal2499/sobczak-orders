<template>
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">{{ title }}</h6>
        </div>
        <div class="card-body">
            <AgreementForm :agreement-id="agreementId" />
        </div>
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
    },
}
</script>
