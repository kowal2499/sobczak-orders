<template>
    <div>
        <SectionBlockTitle block :title="$t('app.title.roles')" :breadcrumbs="breadcrumbs" />
        <AuthConfigurationWrapper>
            <Roles />
        </AuthConfigurationWrapper>
    </div>
</template>

<script>
import AuthConfigurationWrapper from '@/modules/configuration/views/AuthConfigurationWrapper.vue'
import Roles from '@/modules/configuration/views/Roles.vue'

export default {
    name: 'RolesPage',
    components: { AuthConfigurationWrapper, Roles },
    computed: {
        breadcrumbs() {
            return [
                { icon: 'home', href: '/', label: this.$t('app.breadcrumb.home') },
                { label: this.$t('app.breadcrumb.configuration') },
                { label: this.$t('app.breadcrumb.roles') },
            ]
        },
    },
}
</script>
