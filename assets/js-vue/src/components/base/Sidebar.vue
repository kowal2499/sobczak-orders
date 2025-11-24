<script>
import { defineComponent } from 'vue'

export default defineComponent({
    name: 'Sidebar',
    props: {
        title: String,
        sidebarClass: String,
        lazy: {
            type: Boolean,
            default: true
        },
        noCloseOnBackdrop: {
            type: Boolean,
            default: true
        }
    },
    watch: {
        isOpen(val) {
            if (val) {
                this.scrollY = window.scrollY || window.pageYOffset
                const scrollbarGap = window.innerWidth - document.documentElement.clientWidth

                // Ustaw inline style zanim zrobisz body fixed
                document.body.style.top = `-${this.scrollY}px`
                document.body.style.left = '0'
                document.body.style.right = '0'
                document.body.style.width = '100%'
                if (scrollbarGap > 0) {
                    document.body.style.paddingRight = `${scrollbarGap}px`
                }
                document.body.style.position = 'fixed'
                document.body.classList.add('sidebar-open')

                this.$nextTick(() => this.contentHeight = this.calcContentHeight())
            } else {
                document.body.classList.remove('sidebar-open')
                document.body.style.position = ''
                document.body.style.top = ''
                document.body.style.left = ''
                document.body.style.right = ''
                document.body.style.width = ''
                document.body.style.paddingRight = ''
                window.scrollTo(0, this.scrollY)
            }
        }
    },
    beforeUnmount() {
        if (this.isOpen) {
            document.body.classList.remove('sidebar-open')
            document.body.style.position = ''
            document.body.style.top = ''
            document.body.style.left = ''
            document.body.style.right = ''
            document.body.style.width = ''
            document.body.style.paddingRight = ''
            window.scrollTo(0, this.scrollY)
        }
    },

    methods: {
        openSidebar() {
            this.isOpen = true
        },

        closeSidebar() {
            this.isOpen = false
        },

        calcContentHeight() {
            const contentEl = this.$refs.content?.$el || this.$refs.content
            if (!contentEl) { return }
            return contentEl.getBoundingClientRect().height
        }
    },
    data: () => ({
        isOpen: false,
        scrollY: 0,
        contentHeight: null,
    })
})
</script>

<template>
    <div>
        <b-sidebar
            :title="title"
            :lazy="lazy"
            :no-close-on-backdrop="noCloseOnBackdrop"
            :sidebar-class="sidebarClass"
            right backdrop shadow
            v-model="isOpen"
        >
            <template #header>
                <div class="d-flex gap-2 w-100">
                    <h5 class="m-0 text-primary font-weight-bold">{{ title }}</h5>
                    <button type="button" class="close ml-auto mr-0" @click="closeSidebar">
                        &times;
                    </button>
                </div>
            </template>

            <template #default>
                <div ref="content" class="h-100">
                    <slot name="sidebar-content"
                          :open="openSidebar"
                          :close="closeSidebar"
                          :height="contentHeight"
                    />
                </div>
            </template>
            <template #footer>
                <slot name="sidebar-footer" :open="openSidebar" :close="closeSidebar" />
            </template>
        </b-sidebar>

        <slot name="sidebar-action" :open="openSidebar" :close="closeSidebar"></slot>
    </div>
</template>

<style lang="scss">
@import "~bootstrap/scss/functions";
@import "~bootstrap/scss/variables";
@import "~bootstrap/scss/mixins/_breakpoints";

.b-sidebar {
    $sizes: (25, 50, 75, 100);
    $breakpoints: (md, lg, xl);

    // .size-25, .size-50, ...
    @each $s in $sizes {
        &.size-#{$s} {
            width: $s * 1%;
        }
    }

    // .size-md-25, .size-lg-50, ...
    @each $bp in $breakpoints {
        @include media-breakpoint-up($bp) {
            @each $s in $sizes {
                &.size-#{$bp}-#{$s} {
                    width: $s * 1%;
                }
            }
        }
    }
}

body.sidebar-open {
    overflow: hidden;
    top: 0;
}
</style>