<script>
import { defineComponent } from 'vue'

let openCount = 0
let savedScrollY = 0

function lockBodyScroll() {
    if (openCount === 0) {
        savedScrollY = window.scrollY || window.pageYOffset
        const scrollbarGap = window.innerWidth - document.documentElement.clientWidth

        document.body.style.top = `-${savedScrollY}px`
        document.body.style.left = '0'
        document.body.style.right = '0'
        document.body.style.width = '100%'
        if (scrollbarGap > 0) {
            document.body.style.paddingRight = `${scrollbarGap}px`
        }
        document.body.style.position = 'fixed'
        document.body.classList.add('sidebar-open')
    }
    openCount += 1
}

function unlockBodyScroll() {
    openCount = Math.max(0, openCount - 1)
    if (openCount === 0) {
        document.body.classList.remove('sidebar-open')
        document.body.style.position = ''
        document.body.style.top = ''
        document.body.style.left = ''
        document.body.style.right = ''
        document.body.style.width = ''
        document.body.style.paddingRight = ''
        window.scrollTo(0, savedScrollY)
    }
}

export default defineComponent({
    name: 'Sidebar',
    inject: {
        parentSidebarLevel: { default: -1 }
    },
    provide() {
        return {
            parentSidebarLevel: this.effectiveLevel
        }
    },
    props: {
        title: String,
        sidebarClass: String,
        level: {
            type: Number,
            default: null
        },
        lazy: {
            type: Boolean,
            default: true
        },
        noCloseOnBackdrop: {
            type: Boolean,
            default: true
        },
        beforeClose: {
            type: Function,
            default: null
        },
        value: {
            type: Boolean,
            default: false
        }
    },
    computed: {
        effectiveLevel() {
            return this.level !== null ? this.level : this.parentSidebarLevel + 1
        },
        combinedSidebarClass() {
            const levelClass = `sidebar-level-${this.effectiveLevel}`
            return this.sidebarClass ? `${levelClass} ${this.sidebarClass}` : levelClass
        }
    },
    watch: {
        async isOpen(val) {
            if (val) {
                this.bSidebarVisible = true
                this.$emit('input', true)
                return
            }
            if (this.closingInProgress) {
                return
            }
            if (this.beforeClose) {
                this.closingInProgress = true
                const allowed = await this.beforeClose()
                this.closingInProgress = false
                if (!allowed) {
                    this.isOpen = true
                    return
                }
            }
            this.bSidebarVisible = false
            this.$emit('input', false)
        },

        value: {
            handler(val) {
                if (this.isOpen !== val) {
                    this.isOpen = val
                }
            },
            immediate: true
        },

        bSidebarVisible(val) {
            if (!val) {
                return
            }
            // wysokość kontenera nie zależy od animacji wysuwania (translateX),
            // więc można ją zmierzyć od razu, nie czekając na @shown
            this.$nextTick(() => {
                const height = this.calcContentHeight()
                if (height) {
                    this.contentHeight = height
                }
            })
        }
    },
    beforeUnmount() {
        if (this.locked) {
            unlockBodyScroll()
            this.locked = false
        }
    },

    methods: {
        openSidebar() {
            this.isOpen = true
        },

        closeSidebar() {
            this.isOpen = false
        },

        onBSidebarInput(val) {
            if (val) {
                this.bSidebarVisible = true
                return
            }
            if (this.isOpen) {
                this.isOpen = false
            } else {
                this.bSidebarVisible = false
            }
        },

        onShown() {
            if (!this.locked) {
                lockBodyScroll()
                this.locked = true
            }
            if (this.contentHeight === null) {
                this.contentHeight = this.calcContentHeight() || 0
            }
        },

        onHidden() {
            if (this.locked) {
                unlockBodyScroll()
                this.locked = false
            }
            this.$emit('closed')
        },

        calcContentHeight() {
            const contentEl = this.$refs.content?.$el || this.$refs.content
            if (!contentEl) { return }
            return contentEl.getBoundingClientRect().height
        }
    },
    data: () => ({
        isOpen: false,
        bSidebarVisible: false,
        contentHeight: null,
        closingInProgress: false,
        locked: false,
    })
})
</script>

<template>
    <div>
        <b-sidebar
            :title="title"
            :lazy="lazy"
            :no-close-on-backdrop="noCloseOnBackdrop"
            :no-close-on-esc="true"
            :sidebar-class="combinedSidebarClass"
            right backdrop shadow
            :visible="bSidebarVisible"
            @input="onBSidebarInput"
            @shown="onShown"
            @hidden="onHidden"
            @keydown.native.esc="closeSidebar"
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
                    <slot v-if="contentHeight !== null"
                          name="sidebar-content"
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

    > .b-sidebar-body { font-size: 0.95rem; }

    @each $s in $sizes {
        &.size-#{$s} {
            width: $s * 1%;
        }
    }

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

.b-sidebar.sidebar-level-1 { z-index: 1065 !important; }
.b-sidebar.sidebar-level-2 { z-index: 1085 !important; }
.b-sidebar.sidebar-level-3 { z-index: 1105 !important; }

body.sidebar-open {
    overflow: hidden;
    top: 0;
}

body.sidebar-open .vs__dropdown-menu {
    z-index: 1100 !important;
}
</style>
