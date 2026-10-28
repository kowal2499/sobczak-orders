<template>
    <div>
        <SectionBlockTitle block :title="$t('app.title.tags')" :breadcrumbs="breadcrumbs" />
        <TagsConfig />
    </div>
</template>

<script>
import TagsConfig from '@/modules/tags/configurator/TagsConfig.vue'

export default {
    name: 'TagsPage',
    components: { TagsConfig },
    computed: {
        breadcrumbs() {
            return [
                { icon: 'home', href: '/', label: this.$t('app.breadcrumb.home') },
                { label: this.$t('app.breadcrumb.configuration') },
                { label: this.$t('app.breadcrumb.tags') },
            ]
        },
    },
}
</script>
