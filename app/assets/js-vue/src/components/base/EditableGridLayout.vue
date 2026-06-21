<template>
    <!-- Mobile: a plain vertical stack (widgets one under another). -->
    <div v-if="isStacked" class="stacked-grid">
        <div
            v-for="item in stackedItems"
            :key="item.i"
            class="stacked-grid__item"
            :style="{ height: itemHeightPx(item) + 'px' }"
        >
            <slot :item="item" />
        </div>
    </div>

    <!--
        Desktop: the draggable/resizable grid.
        use-css-transforms MUST stay false: CSS transforms on the grid items
        create a containing block for position:fixed descendants, which traps
        any fixed overlay rendered inside a widget (e.g. a b-sidebar / modal)
        within that widget instead of the viewport. top/left positioning avoids it.
    -->
    <grid-layout
        v-else
        :layout="internalLayout"
        :col-num="colNum"
        :row-height="rowHeight"
        :is-draggable="editable"
        :is-resizable="editable"
        :vertical-compact="true"
        :use-css-transforms="false"
        :margin="[margin, margin]"
        @layout-updated="onLayoutUpdated"
    >
        <grid-item
            v-for="item in internalLayout"
            :key="item.i"
            :i="item.i"
            :x="item.x"
            :y="item.y"
            :w="item.w"
            :h="item.h"
        >
            <slot :item="item" />
        </grid-item>
    </grid-layout>
</template>

<script>
import { GridLayout, GridItem } from "vue-grid-layout";

/**
 * Generic grid of draggable/resizable slots. Owns only positioning state
 * (layout array of {i, x, y, w, h}) — visibility, persistence and what's
 * rendered inside each slot are left entirely to the caller via the
 * default scoped slot.
 *
 * Below `mobileBreakpoint` the grid is replaced by a simple vertical stack:
 * vue-grid-layout's own responsive mode only works with `:layout.sync` (it
 * emits a recomputed layout the parent must adopt), so reusing the desktop
 * x/y here would collapse side-by-side widgets onto each other. A plain
 * stack avoids that and never touches the persisted desktop layout.
 */
export default {
    name: "EditableGridLayout",

    components: {
        GridLayout,
        GridItem,
    },

    props: {
        layout: {
            type: Array,
            required: true,
        },
        editable: {
            type: Boolean,
            default: false,
        },
        colNum: {
            type: Number,
            default: 12,
        },
        rowHeight: {
            type: Number,
            default: 30,
        },
        margin: {
            type: Number,
            default: 16,
        },
        mobileBreakpoint: {
            type: Number,
            default: 767.98,
        },
    },

    data() {
        return {
            internalLayout: this.cloneLayout(this.layout),
            isStacked: false,
        };
    },

    computed: {
        // top-to-bottom, left-to-right order so the stack matches the grid
        stackedItems() {
            return [...this.internalLayout].sort((a, b) => a.y - b.y || a.x - b.x);
        },
    },

    watch: {
        layout: {
            deep: true,
            handler(value) {
                this.internalLayout = this.cloneLayout(value);
            },
        },
    },

    mounted() {
        this.mql = window.matchMedia(`(max-width: ${this.mobileBreakpoint}px)`);
        this.isStacked = this.mql.matches;
        this.onMqlChange = (event) => { this.isStacked = event.matches; };
        this.mql.addEventListener("change", this.onMqlChange);
    },

    beforeDestroy() {
        if (this.mql) {
            this.mql.removeEventListener("change", this.onMqlChange);
        }
    },

    methods: {
        cloneLayout(layout) {
            return layout.map(item => ({ ...item }));
        },
        layoutSignature(layout) {
            return layout
                .map(item => `${item.i}:${item.x}:${item.y}:${item.w}:${item.h}`)
                .sort()
                .join("|");
        },
        // same pixel height a widget would occupy in the grid, so widgets that
        // rely on a defined height (e.g. charts) render identically when stacked
        itemHeightPx(item) {
            return item.h * this.rowHeight + (item.h - 1) * this.margin;
        },
        onLayoutUpdated(newLayout) {
            if (this.layoutSignature(newLayout) === this.layoutSignature(this.layout)) {
                return;
            }
            this.$emit("update:layout", this.cloneLayout(newLayout));
        },
    },
};
</script>

<style scoped lang="scss">
.stacked-grid {
    display: flex;
    flex-direction: column;
    gap: 16px;

    &__item {
        position: relative;
    }
}
</style>
