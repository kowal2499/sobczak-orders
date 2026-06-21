<template>
    <div class="section-block-title" :class="{ 'section-block-title--block': block }">
        <div class="section-block-title__head">
            <span class="section-block-title__title">
                <slot>{{ title }}</slot>
            </span>

            <nav
                v-if="breadcrumbs && breadcrumbs.length"
                aria-label="breadcrumb"
                class="section-block-title__breadcrumb"
            >
                <ol class="breadcrumb">
                    <li
                        v-for="(crumb, i) in breadcrumbs"
                        :key="i"
                        class="breadcrumb-item"
                        :class="{ active: isLast(i) }"
                        :aria-current="isLast(i) ? 'page' : null"
                    >
                        <component
                            :is="crumb.href && !isLast(i) ? 'a' : 'span'"
                            :href="crumb.href && !isLast(i) ? crumb.href : null"
                            :aria-label="crumb.icon ? crumb.label : null"
                        >
                            <font-awesome-icon v-if="crumb.icon" :icon="crumb.icon" />
                            <template v-else>{{ crumb.label }}</template>
                        </component>
                    </li>
                </ol>
            </nav>
        </div>

        <template v-if="hasFilters">
            <hr class="section-block-title__divider">
            <div class="section-block-title__filters">
                <slot name="filters" />
            </div>
        </template>
    </div>
</template>

<script>
/**
 * Title cluster for a view header: the title and an optional breadcrumb
 * beneath it, with shared muted styling so breadcrumbs stay secondary to
 * the title across views.
 *
 * `breadcrumbs` is a list of `{ label, href?, icon? }`. The last entry is
 * the active (non-linked) crumb. An `icon` (FontAwesome name) renders in
 * place of the label, keeping `label` as its aria-label.
 *
 * Set `block` to render the component as a standalone SectionBlock-style
 * panel (instead of an inline cluster nested in a SectionBlock). In that
 * mode the optional `#filters` slot is rendered full width below the
 * title, separated by a horizontal divider.
 */
export default {
    name: "SectionBlockTitle",
    props: {
        title: {
            type: String,
            default: "",
        },
        breadcrumbs: {
            type: Array,
            default: () => [],
        },
        block: {
            type: Boolean,
            default: false,
        },
    },
    computed: {
        // `#filters` is compiled as a scoped slot in Vue 2.6, so it may live in
        // $scopedSlots rather than $slots — check both, otherwise the filters
        // block silently fails to render inside the title panel.
        hasFilters() {
            return !!(this.$slots.filters || this.$scopedSlots.filters);
        },
    },
    methods: {
        isLast(index) {
            return index === this.breadcrumbs.length - 1;
        },
    },
};
</script>

<style scoped lang="scss">
// Standalone panel — mirrors SectionBlock so the header can be its own block.
.section-block-title--block {
    background-color: #fff;
    padding: 1rem;
    border-radius: 1rem;
    border: 1px solid #ddd;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
}

// Title left, breadcrumb right, aligned on the same line.
.section-block-title__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}

.section-block-title__title {
    font-size: 1rem;
    font-weight: 600;
    color: var(--colorPrimary);
}

.section-block-title__breadcrumb {
    .breadcrumb {
        margin-bottom: 0;
        padding: 0;
        background-color: transparent;
        font-size: 0.8rem;
    }

    // Muted so the breadcrumb stays secondary to the title.
    .breadcrumb-item,
    .breadcrumb-item.active,
    .breadcrumb-item + .breadcrumb-item::before,
    .breadcrumb-item a,
    .breadcrumb-item span {
        color: #b0b6bd;
    }

    .breadcrumb-item a:hover {
        color: var(--colorPrimary);
    }

    // Icons keep the brand colour as the only accent.
    .breadcrumb-item svg {
        color: var(--colorPrimary);
    }
}

.section-block-title__divider {
    margin: 0.85rem 0;
    border: 0;
    border-top: 1px solid #e9ecef;
}

.section-block-title__filters {
    width: 100%;
}
</style>
