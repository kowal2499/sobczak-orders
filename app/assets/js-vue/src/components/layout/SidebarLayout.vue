<template>
    <div class="sidebar-layout d-flex flex-column gap-3 h-100 w-100">
        <slot name="header" />
        <div class="sidebar-layout__content d-flex flex-column gap-3 flex-grow-1 overflow-auto">
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
.sidebar-layout {
    padding: 0 1.25rem 1.25rem;
}

// The scrollbar sits in the layout's right padding, so content keeps a gap before it
// and, without a scrollbar, still ends level with the header.
.sidebar-layout__content {
    min-height: 0;
    margin-right: -0.75rem;
    padding-right: 0.75rem;
}
</style>
