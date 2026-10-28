<template>
    <ErrorPage :title="$t('app.title.notFound')" :message="$t('app.notFound')" />
</template>

<script>
import ErrorPage from '../components/ErrorPage.vue'

export default {
    name: 'NotFoundPage',
    components: { ErrorPage },
}
</script>
