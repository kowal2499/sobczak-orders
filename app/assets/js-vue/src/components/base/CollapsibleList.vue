<template>
    <div>
        <div class="collapsible-list" :style="containerStyle" ref="container">
            <div
                class="collapsible-list__items"
                :class="{ 'collapsible-list__items-has-more': hasMore }"
                ref="itemsContainer"
            >
                <slot
                    v-for="(item, index) in visibleItems"
                    :item="item"
                    :index="index"
                />
            </div>

            <div v-if="hasMore" class="collapsible-list__toggler">
                <button
                    type="button"
                    class="expand-button btn btn-sm btn-outline-primary w-100 text-nowrap position-relative"
                    @click="toggleExpanded"
                >
                    <font-awesome-icon
                        :icon="isExpanded ? 'chevron-down' : 'chevron-up'"
                        size="xs"
                    />
                    <b-badge v-if="hasMore"
                             >
                        {{ items.length }}</b-badge>
                </button>


            </div>
        </div>

        <portal :to="portalName" v-if="isExpanded">
            <div
                class="collapsible-list__overlay"
                :style="overlayStyle"
                v-click-outside="closeExpanded"
            >
                <div class="collapsible-list__expanded-items">
                    <slot
                        v-for="(item, index) in items"
                        :item="item"
                        :index="index"
                    />
                </div>

            </div>
        </portal>

        <portal-target :name="portalName" />
    </div>
</template>

<script>
// Globalny event bus dla synchronizacji wielu instancji
const collapsibleListInstances = new Set()

export default {
    name: 'CollapsibleList',

    props: {
        items: {
            type: Array,
            required: true
        },
        visibleRows: {
            type: Number,
            default: 3
        },
        expandLabel: {
            type: String,
            default: 'Pokaż wszystkie'
        },
        collapseLabel: {
            type: String,
            default: 'Zwiń'
        }
    },

    directives: {
        'click-outside': {
            bind(el, binding, vnode) {
                el._clickOutsideHandler = (event) => {
                    // Sprawdź czy element jest nadal w DOM
                    if (!document.body.contains(event.target)) {
                        return
                    }

                    // Sprawdź czy kliknięcie nie jest na renderowanym overlay
                    if (el.contains(event.target)) {
                        return
                    }

                    // Sprawdź czy kliknięcie nie jest na oryginalnym komponencie (toggle button)
                    const component = vnode.context
                    if (component.$el && component.$el.contains(event.target)) {
                        return
                    }

                    // Sprawdź czy kliknięcie nie jest na innym CollapsibleList
                    for (const instance of collapsibleListInstances) {
                        if (instance.$el && instance.$el.contains(event.target)) {
                            return
                        }
                    }

                    binding.value(event)
                }
                setTimeout(() => {
                    document.addEventListener('click', el._clickOutsideHandler)
                }, 0)
            },
            unbind(el) {
                document.removeEventListener('click', el._clickOutsideHandler)
            }
        }
    },

    data() {
        return {
            isExpanded: false,
            overlayStyle: {},
            scrollParents: [],
            clippingParents: [],
            containerStyle: {},
            portalName: `collapsible-list-portal-${Math.random().toString(36).substr(2, 9)}`,
        }
    },

    computed: {
        visibleItems() {
            return this.items.slice(0, this.visibleRows)
        },

        hasMore() {
            return this.items.length > this.visibleRows
        }
    },

    created() {
        collapsibleListInstances.add(this)
    },

    beforeDestroy() {
        collapsibleListInstances.delete(this)
        this.removeScrollListeners()
    },

    methods: {
        toggleExpanded() {
            if (!this.isExpanded) {
                // Zamknij wszystkie inne rozwinięte instancje
                this.closeOtherInstances()

                // Zapisz szerokość przed rozwinięciem
                const containerRect = this.$refs.container.getBoundingClientRect()
                this.containerStyle = {
                    width: `${containerRect.width}px`,
                    height: `${containerRect.height}px`,
                }

                this.calculateOverlayPosition()
                this.$nextTick(() => {
                    this.addScrollListeners()
                })
                this.isExpanded = true
            } else {
                this.removeScrollListeners()
                this.isExpanded = false
                this.containerStyle = {}
            }
        },

        closeOtherInstances() {
            for (const instance of collapsibleListInstances) {
                if (instance !== this && instance.isExpanded) {
                    instance.closeExpanded()
                }
            }
        },

        closeExpanded() {
            this.isExpanded = false
            this.containerStyle = {}
            this.removeScrollListeners()
        },

        calculateOverlayPosition() {
            const rect = this.$refs.itemsContainer.getBoundingClientRect()
            this.overlayStyle = {
                position: 'fixed',
                top: `${rect.top}px`,
                left: `${rect.left}px`,
                width: `${rect.width}px`,
                zIndex: 1050
            }
        },

        getScrollParents(element) {
            const scrollParents = []
            let parent = element.parentElement

            while (parent) {
                const style = getComputedStyle(parent)
                const overflow = style.overflow + style.overflowX + style.overflowY

                if (/(auto|scroll)/.test(overflow)) {
                    scrollParents.push(parent)
                }
                parent = parent.parentElement
            }

            scrollParents.push(window)
            return scrollParents
        },

        getClippingParents(element) {
            const clippingParents = []
            let parent = element.parentElement

            while (parent) {
                const style = getComputedStyle(parent)
                const overflow = style.overflow + style.overflowX + style.overflowY

                // Wykryj wszystkie elementy z overflow: hidden, auto lub scroll
                if (/(auto|scroll|hidden)/.test(overflow)) {
                    clippingParents.push(parent)
                }
                parent = parent.parentElement
            }

            return clippingParents
        },

        onScroll() {
            // Sprawdź czy komponent jest nadal widoczny w obszarze clipping parents
            if (!this.isVisibleInClippingParents()) {
                this.closeExpanded()
                return
            }

            // Aktualizuj pozycję overlay
            this.calculateOverlayPosition()
        },

        isVisibleInClippingParents() {
            const rect = this.$refs.itemsContainer.getBoundingClientRect()

            for (const parent of this.clippingParents) {
                const parentRect = parent.getBoundingClientRect()

                // Sprawdź czy komponent jest całkowicie poza widocznym obszarem rodzica
                const isOutsideHorizontally = rect.left < parentRect.left || rect.right > parentRect.right
                const isOutsideVertically = rect.bottom < parentRect.top || rect.top > parentRect.bottom

                if (isOutsideHorizontally || isOutsideVertically) {
                    return false
                }
            }

            return true
        },

        addScrollListeners() {
            this.scrollParents = this.getScrollParents(this.$refs.itemsContainer)
            this.clippingParents = this.getClippingParents(this.$refs.itemsContainer)
            this.scrollParents.forEach(parent => {
                parent.addEventListener('scroll', this.onScroll, { passive: true })
            })
        },

        removeScrollListeners() {
            this.scrollParents.forEach(parent => {
                parent.removeEventListener('scroll', this.onScroll)
            })
            this.scrollParents = []
            this.clippingParents = []
        }
    }
}
</script>

<style scoped>
.collapsible-list {
    border-radius: 4px;
    display: flex;
    width: 100%;
}

.collapsible-list__items {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 2px;
    padding: 8px;
}

.collapsible-list__items-has-more {
    border: 1px solid var(--colorPrimary);
    border-radius: 4px;
}

.collapsible-list__toggler {
    flex: 0 0 25px;
    width: 25px;

    button {
        box-shadow: none;
        position: relative;

        .badge {
            position: absolute;
            top: 0;
            right: 0;
            transform: translate(50%, -50%);
            background-color: var(--colorPrimary);
            color: var(--colorWhite);
        }

    }
}

.collapsible-list__overlay {
    background: #fff;
    border: 1px solid var(--colorPrimary);
    border-radius: 4px;
}

.collapsible-list__expanded-items {
    padding: 8px;
    display: flex;
    flex-direction: column;
    gap: 2px;
}
</style>