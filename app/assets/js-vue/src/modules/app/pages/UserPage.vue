<template>
    <div>
        <SectionBlockTitle block :title="title" :breadcrumbs="breadcrumbs">
            <template #actions>
                <!-- the form is long, so saving is also reachable without scrolling to its end -->
                <button
                    v-if="formState"
                    type="button"
                    class="btn btn-primary btn-sm"
                    :disabled="!formState.canSubmit"
                    @click="$refs.form.save()"
                >
                    <i v-if="formState.busy" class="fa fa-spinner fa-spin" aria-hidden="true"></i>
                    {{ formState.label }}
                </button>
            </template>
        </SectionBlockTitle>
        <AuthConfigurationWrapper>
            <UserSingle ref="form" :user-id="userId" @state="formState = $event" />
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
    data: () => ({
        formState: null,
    }),
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
