<template>
    <span>
        <div class="tag-wrapper"
             v-for="log in logs"
             v-b-tooltip.hover :title="log.tagDefinition.name"
        >
            <b-icon
                :icon="log.tagDefinition.icon"
                :style="{color: log.tagDefinition.color}"/>
        </div>
    </span>
</template>

<script>
export default {
    name: "TagsIndicator",
    props: {
        logs: {
            type: Array,
            default: () => ([])
        }
    }
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