<template>

    <span
        @mouseenter="mouseover = true"
        @mouseleave="mouseover = false"
        class="tooltip-wrapper"
    >
        <slot
            name="visible-content"
        ></slot>

        <div class="tooltip-body" v-if="true === mouseover">
            <slot name="tooltip-content"></slot>
        </div>
    </span>

</template>

<script>
    export default {
        name: "Tooltip",

        data() {
            return {
                mouseover: false,
            }
        }
    }
</script>

<style scoped lang="scss">

    .tooltip-wrapper {
        position: relative;

        .tooltip-body {
            font-family: 'Roboto', sans-serif;
            position: absolute;
            top: calc(10% + 15px);
            left: calc(100% + 15px);
            width: 200px;
            background-color: lightyellow;
            border: 1px solid #ccc;
            border-radius: 2px;

            font-size: 0.875rem;
            line-height: 1.175rem;
            padding: 10px;
            opacity: 0.9;

            z-index: 20;
        }
    }

</style>