<template>
    <section class="collapsible-section" :class="{ 'collapsible-section--flush': noPadding }">
        <div class="collapsible-section__header" @click="collapsed = !collapsed">
            <button type="button" class="collapsible-section__title" :aria-expanded="String(!collapsed)">
                {{ title }}
            </button>
            <div v-if="!collapsed && $slots.header" class="collapsible-section__header-slot" @click.stop>
                <slot name="header"></slot>
            </div>
            <i
                class="fa fa-chevron-down collapsible-section__chevron"
                :class="{ 'fa-rotate-180': collapsed }"
                aria-hidden="true"
            ></i>
        </div>

        <div v-show="!collapsed" class="collapsible-section__body">
            <div class="card-filters">
                <slot name="filters"></slot>
            </div>
            <slot></slot>
        </div>
    </section>
</template>

<script>
    export default {
        name: "CollapsibleCard",

        props: {
            title: String,
            locked: {
                type: Boolean,
                default: false
            },
            noPadding: {
                type: Boolean,
                default: false
            },
            collapsedOnStart: {
                type: Boolean,
                default: false
            }
        },

        created() {
            if (this.collapsedOnStart) {
                this.collapsed = true;
            }
        },

        data() {
            return {
                collapsed: false
            }
        }
    }
</script>

<style lang="scss" scoped>
.collapsible-section {
    background-color: var(--colorSurface);
    border-radius: var(--surfaceRadius);
    padding: 1rem;
    margin-bottom: 1rem;
    font-size: var(--fontSizeContent);

    &--flush .collapsible-section__body {
        margin-left: -1rem;
        margin-right: -1rem;
        margin-bottom: -1rem;
    }
}

.collapsible-section__header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    cursor: pointer;
}

.collapsible-section__title {
    flex: 1 1 auto;
    min-width: 0;
    padding: 0;
    border: 0;
    background: transparent;
    text-align: left;
    font-size: 1rem;
    font-weight: 600;
    color: var(--colorPrimary);

    &:focus-visible {
        outline: 2px solid var(--colorPrimary);
        outline-offset: 2px;
        border-radius: 0.25rem;
    }
}

.collapsible-section__header-slot {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    cursor: default;
}

.collapsible-section__chevron {
    color: var(--colorSecondary);
    transition: transform 0.2s;

    @media (prefers-reduced-motion: reduce) {
        transition: none;
    }
}

.collapsible-section__body {
    margin-top: 0.75rem;
}
</style>
