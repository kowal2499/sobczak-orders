<template>
    <div class="section-block">
        <slot />
    </div>
</template>

<script>
/**
 * Generic white rounded panel used to group a view into sections
 * (e.g. a title/filters bar and a content area). Layout of the inner
 * content is left to the caller — pass utility classes on the element
 * itself (e.g. `class="d-flex justify-content-between"`), they merge
 * onto the root.
 */
export default {
    name: "SectionBlock",
};
</script>

<style scoped lang="scss">
.section-block {
    background-color: #fff;
    padding: 1rem;
    border-radius: 1rem;
    border: 1px solid #ddd;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
}
</style>
