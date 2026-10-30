<template>
    <div
        class="section-block-title"
        :class="{ 'section-block-title--block': block, 'section-block-title--with-actions': hasActions }"
    >
        <div class="section-block-title__head">
            <div class="section-block-title__heading">
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
                                :is="crumbTag(crumb, i)"
                                :href="crumbTag(crumb, i) === 'a' ? crumb.href : null"
                                :to="crumbTag(crumb, i) === 'router-link' ? crumb.href : null"
                                :aria-label="crumb.icon ? crumb.label : null"
                            >
                                <font-awesome-icon v-if="crumb.icon" :icon="crumb.icon" />
                                <template v-else>{{ crumb.label }}</template>
                            </component>
                        </li>
                    </ol>
                </nav>
            </div>

            <div v-if="hasActions" class="section-block-title__actions">
                <slot name="actions" />
            </div>
        </div>

        <div v-if="hasFilters" class="section-block-title__filters">
            <slot name="filters" />
        </div>
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
 * Set `block` to render it as the page header placed directly on the canvas
 * (instead of an inline cluster nested in a SectionBlock): a large title with
 * the breadcrumb beneath it, and the optional `#filters` slot full width below.
 *
 * The optional `#actions` slot (e.g. a "create" button) sits on the right;
 * the breadcrumb then moves beneath the title to make room for it.
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
        // $scopedSlots rather than $slots - check both, otherwise the filters
        // block silently fails to render inside the title panel.
        hasFilters() {
            return !!(this.$slots.filters || this.$scopedSlots.filters);
        },
        hasActions() {
            return !!(this.$slots.actions || this.$scopedSlots.actions);
        },
    },
    methods: {
        isLast(index) {
            return index === this.breadcrumbs.length - 1;
        },
        // Paths the router does not know must reload the page.
        crumbTag(crumb, index) {
            if (!crumb.href || this.isLast(index)) {
                return 'span';
            }
            if (this.$router.resolve(crumb.href).route.name !== 'not-found') {
                return 'router-link';
            }
            return 'a';
        },
    },
};
</script>

<style scoped lang="scss">
.section-block-title__head {
    display: flex;
    align-items: center;
    gap: 1rem;
}

// Title left, breadcrumb right, aligned on the same line.
.section-block-title__heading {
    flex: 1 1 auto;
    min-width: 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}

.section-block-title--with-actions .section-block-title__heading,
.section-block-title--block .section-block-title__heading {
    flex-direction: column;
    align-items: flex-start;
    justify-content: center;
    gap: 0.15rem;
}

.section-block-title__actions {
    flex: 0 0 auto;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.section-block-title__title {
    font-size: 1rem;
    font-weight: 600;
    color: var(--colorPrimary);
}

.section-block-title--block .section-block-title__title {
    font-size: 1.5rem;
    font-weight: 700;
    line-height: 1.2;
    letter-spacing: -0.01em;
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
        color: var(--colorSecondary);
    }

    .breadcrumb-item a {
        cursor: pointer;
    }

    .breadcrumb-item a:hover {
        color: var(--colorPrimary);
    }

    // Icons keep the brand colour as the only accent.
    .breadcrumb-item svg {
        color: var(--colorPrimary);
    }
}

.section-block-title__filters {
    width: 100%;
    margin-top: 1rem;
}
</style>
