<template>
    <div class="overlay-wrapper" >
        <slot></slot>
        <transition name="fade">
            <div class="courtain" v-if="show">
                <stretch background="#4e73df"/>
            </div>
        </transition>
    </div>
</template>

<script>

import {Stretch} from 'vue-loading-spinner';
export default {
    name: "Overlay",

    components: {
        Stretch
    },

    props: {
        show: {
            type: Boolean,
            default: false
        }
    }
}
</script>

<style scoped lang="scss">
    .overlay-wrapper {
        position: relative;

        .courtain {
            position: absolute;
            width: 100%;
            height: 100%;
            left: 0;
            right: 0;
            top: 0;
            bottom: 0;

            display: flex;
            justify-content: center;
            align-items: center;

            overflow: hidden;

            background-color: rgba(255, 255, 255, 0.6);
        }

        .fade-enter-active, .fade-leave-active {
            transition: opacity .3s;
        }
        .fade-enter, .fade-leave-to {
            opacity: 0;
        }
    }
</style>