<template>
    <div class="d-flex flex-column gap-3 h-100 w-100">
        <slot name="header" />
        <div class="d-flex flex-column gap-3 flex-1 overflow-auto">
            <slot name="content" />
        </div>
    </div>
</template>

<script>

export default {
    name: "SidebarLayout",
}
</script>

<style scoped lang="scss">
</style>