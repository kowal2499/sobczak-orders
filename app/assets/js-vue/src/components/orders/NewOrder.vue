<template>
    <component
        :is="component"
        :agreement-id="agreementId"
    />
</template>

<script>
export default {
    name: 'NewOrder',

    props: {
        agreementId: {
            type: Number,
            default: 0
        }
    },

    mounted() {
        const urlParams = new URLSearchParams(window.location.search)
        this.component = urlParams.has('test')
            ? () => import('@/modules/agreement/view/AgreementForm.vue')
            : () => import('./NewOrderLegacy.vue')
    },

    data: () => ({
        component: null
    })
}
</script>

<style lang="scss" scoped>

</style>