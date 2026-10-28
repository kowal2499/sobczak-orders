<template>
    <UsersList :page-title="$t('app.title.users')" :breadcrumbs="breadcrumbs" />
</template>

<script>
import UsersList from '@/components/users/UsersList.vue'

export default {
    name: 'UsersPage',
    components: { UsersList },
    computed: {
        breadcrumbs() {
            return [
                { icon: 'home', href: '/', label: this.$t('app.breadcrumb.home') },
                { label: this.$t('app.breadcrumb.configuration') },
                { label: this.$t('app.breadcrumb.users') },
            ]
        },
    },
}
</script>
