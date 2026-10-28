<template>
    <div>
        <SectionBlockTitle block :title="$t('app.title.workConfiguration')" :breadcrumbs="breadcrumbs" />
        <ProductionConfiguration />
    </div>
</template>

<script>
import ProductionConfiguration from '@/modules/configuration/views/Production.vue'

export default {
    name: 'WorkConfigurationPage',
    components: { ProductionConfiguration },
    computed: {
        breadcrumbs() {
            return [
                { icon: 'home', href: '/', label: this.$t('app.breadcrumb.home') },
                { label: this.$t('app.breadcrumb.configuration') },
                { label: this.$t('app.breadcrumb.workConfiguration') },
            ]
        },
    },
}
</script>
