<template>
    <span>
        <tag
            v-for="({tagDefinition}, key) in logs"
            :key="key"
            :color="tagDefinition.color"
            :icon="tagDefinition.icon"
            :name="tagDefinition.name"
        />
    </span>
</template>

<script>
import Tag from "./Tag.vue"

export default {
    name: "TagsIndicator",
    props: {
        logs: {
            type: Array,
            default: () => ([])
        }
    },
    components: { Tag }
}
</script>

<style scoped lang="scss">
    .tag-wrapper {
        padding: 2px 4px;
        border: 1px solid lightgray;
        border-radius: 3px;
        margin: 0 3px 0 0;
        display: inline-block;
    }
</style>