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
        <!-- The order panel opens via ?panel= over other drawers; a path change destroys the view anyway. -->
        <b-sidebar
            :title="title"
            :lazy="lazy"
            :no-close-on-backdrop="noCloseOnBackdrop"
            :no-close-on-esc="true"
            no-close-on-route-change
            :sidebar-class="combinedSidebarClass"
            right backdrop shadow
            bg-variant=""
            text-variant=""
            :visible="bSidebarVisible"
            @input="onBSidebarInput"
            @shown="onShown"
            @hidden="onHidden"
            @keydown.native.esc="closeSidebar"
        >
            <template #header>
                <div class="sidebar-header">
                    <h5 class="sidebar-header__title">{{ title }}</h5>
                    <div v-if="$scopedSlots['sidebar-header-actions']" class="sidebar-header__actions">
                        <slot name="sidebar-header-actions" :close="closeSidebar" />
                    </div>
                    <button
                        type="button"
                        class="btn btn-primary btn-sm sidebar-header__close"
                        :aria-label="$t('_close')"
                        :title="$t('_close')"
                        @click="closeSidebar"
                    >
                        <i class="fa fa-times" aria-hidden="true"></i>
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
    $breakpoints: (md, lg, xl, xxl);
    // Bootstrap 4 nie ma progu xxl; 50% szerokości poniżej 1400px jest za wąskie na listy kart
    $sidebar-breakpoints: map-merge($grid-breakpoints, (xxl: 1400px));

    background-color: var(--colorCanvas);
    color: var(--colorInk);

    > .b-sidebar-header {
        padding: 1.25rem 1.25rem 0.75rem;
    }

    > .b-sidebar-body { font-size: var(--fontSizeContent); }

    > .b-sidebar-footer:empty { display: none; }

    .sidebar-header {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        width: 100%;
    }

    .sidebar-header__title {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 700;
        line-height: 1.2;
        color: var(--colorPrimary);
    }

    .sidebar-header__actions {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-left: auto;
    }

    .sidebar-header__close {
        margin-left: auto;
    }

    .sidebar-header__actions + .sidebar-header__close {
        margin-left: 0.5rem;
    }

    @each $s in $sizes {
        &.size-#{$s} {
            width: $s * 1%;
        }
    }

    @each $bp in $breakpoints {
        @include media-breakpoint-up($bp, $sidebar-breakpoints) {
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
