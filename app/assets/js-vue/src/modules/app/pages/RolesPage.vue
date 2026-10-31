<template>
    <div>
        <SectionBlockTitle block :title="$t('app.title.roles')" :breadcrumbs="breadcrumbs">
            <template #actions>
                <RoleModalForm @roleCreated="onRoleCreated" />
            </template>
        </SectionBlockTitle>
        <AuthConfigurationWrapper>
            <Roles ref="roles" />
        </AuthConfigurationWrapper>
    </div>
</template>

<script>
import AuthConfigurationWrapper from '@/modules/configuration/views/AuthConfigurationWrapper.vue'
import Roles from '@/modules/configuration/views/Roles.vue'
import RoleModalForm from '@/modules/configuration/components/forms/RoleModalForm.vue'

export default {
    name: 'RolesPage',
    components: { AuthConfigurationWrapper, Roles, RoleModalForm },
    computed: {
        breadcrumbs() {
            return [
                { icon: 'home', href: '/', label: this.$t('app.breadcrumb.home') },
                { label: this.$t('app.breadcrumb.configuration') },
                { label: this.$t('app.breadcrumb.roles') },
            ]
        },
    },
    methods: {
        onRoleCreated() {
            if (this.$refs.roles) {
                this.$refs.roles.reset()
            }
        },
    },
}
</script>
