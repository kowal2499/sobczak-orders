<template>
    <div class="tag-wrapper"
         v-b-tooltip.hover :title="name"
    >
        <b-icon
            :icon="icon"
            :style="{ color }"/>
    </div>
</template>

<script>
export default {
    name: "Tag",
    props: {
        name: {
            type: String,
            required: true
        },
        icon: {
            type: String,
            required: true
        },
        color: {
            type: String,
            required: true
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