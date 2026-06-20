<template>
    <grid-layout
        :layout="internalLayout"
        :col-num="colNum"
        :row-height="rowHeight"
        :is-draggable="editable"
        :is-resizable="editable"
        :vertical-compact="true"
        :use-css-transforms="true"
        :margin="[16, 16]"
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
    },

    data() {
        return {
            internalLayout: this.cloneLayout(this.layout),
        };
    },

    watch: {
        layout: {
            deep: true,
            handler(value) {
                this.internalLayout = this.cloneLayout(value);
            },
        },
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
</style>
