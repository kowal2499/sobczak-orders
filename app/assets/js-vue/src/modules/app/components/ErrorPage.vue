<template>
    <SectionBlock>
        <SectionBlockTitle :title="title" />
        <p class="mt-3 mb-3 text-muted">{{ message }}</p>
        <router-link :to="{ name: 'dashboard' }" class="btn btn-light btn-sm">
            <font-awesome-icon icon="home" class="mr-1" /> {{ $t('app.backHome') }}
        </router-link>
    </SectionBlock>
</template>

<script>
export default {
    name: 'ErrorPage',
    props: {
        title: { type: String, required: true },
        message: { type: String, required: true },
    },
}
</script>
