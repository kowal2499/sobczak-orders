<template>
    <div class="section-block">
        <slot />
    </div>
</template>

<script>
/**
 * White content surface on the page canvas. Holds the view's main content only;
 * the title, breadcrumbs and filters sit on the canvas above it (SectionBlockTitle).
 * Layout of the inner content is left to the caller - utility classes passed on
 * the element merge onto the root.
 */
export default {
    name: "SectionBlock",
};
</script>

<style scoped lang="scss">
.section-block {
    background-color: var(--colorSurface);
    padding: 1rem;
    border-radius: var(--surfaceRadius);
    font-size: var(--fontSizeContent);
}
</style>
