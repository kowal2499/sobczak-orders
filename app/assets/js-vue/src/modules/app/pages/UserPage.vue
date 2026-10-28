<template>
    <div>
        <SectionBlockTitle block :title="title" :breadcrumbs="breadcrumbs" />
        <AuthConfigurationWrapper>
            <UserSingle :user-id="userId" />
        </AuthConfigurationWrapper>
    </div>
</template>

<script>
import AuthConfigurationWrapper from '@/modules/configuration/views/AuthConfigurationWrapper.vue'
import UserSingle from '@/modules/configuration/views/UserSingle.vue'

export default {
    name: 'UserPage',
    components: { AuthConfigurationWrapper, UserSingle },
    props: {
        userId: { type: Number, default: 0 },
    },
    computed: {
        title() {
            return this.$t(this.userId ? 'app.title.userEdit' : 'app.title.userNew')
        },
        breadcrumbs() {
            return [
                { icon: 'home', href: '/', label: this.$t('app.breadcrumb.home') },
                { label: this.$t('app.breadcrumb.configuration') },
                { href: '/users', label: this.$t('app.breadcrumb.users') },
                { label: this.title },
            ]
        },
    },
}
</script>
