<script>
import { agreementStatusesMap } from '@/helpers'
import OrderPanelDrawer from '@/modules/agreement/components/OrderPanelDrawer.vue'

// Numer, klient, produkt i status zamówienia z linkiem do panelu - wspólne dla kart w drawerach
// i kolumny zamówienia w tabelach szczegółów mierników.
export default {
    name: 'AgreementLineHeading',
    components: { OrderPanelDrawer },
    props: {
        agreementLineId: {
            type: Number,
            default: null
        },
        // numer do wyświetlenia, już z sufiksem internalNumber
        orderNumber: {
            type: String,
            default: ''
        },
        customerName: {
            type: String,
            default: ''
        },
        productName: {
            type: String,
            default: ''
        },
        status: {
            type: [Number, String],
            default: null
        },
        panelDepartment: {
            type: String,
            default: null
        },
        compact: {
            type: Boolean,
            default: false
        },
    },
    computed: {
        lineStatus() {
            return agreementStatusesMap[this.status] || null
        },
        canOpenPanel() {
            return !!this.agreementLineId && this.$user.can('production.panel')
        },
        panelUrl() {
            return `/agreement/line/${this.agreementLineId}`
        },
    },
    methods: {
        openPanel() {
            this.panelMounted = true
            this.panelOpen = false
            this.$nextTick(() => {
                this.panelOpen = true
            })
        },
        onPanelSaved() {
            this.savedInPanel = true
        },
        // Listy karmią różni właściciele danych (pulpit, kalendarz, raport) - każdy przeładowuje swoje
        // źródło. Dopiero po zamknięciu panelu: przeładowanie może przebudować wiersz, który go trzyma,
        // a drawer zniszczony w trakcie animacji nie cofnąłby adresu ani blokady scrolla.
        onPanelClosed() {
            if (!this.savedInPanel) {
                return
            }
            this.savedInPanel = false
            EventBus.$emit('agreementLineSaved', this.agreementLineId)
        },
    },
    data: () => ({
        panelMounted: false,
        panelOpen: false,
        savedInPanel: false,
    }),
}
</script>

<template>
    <div class="line-heading" :class="{ 'line-heading--compact': compact }">
        <div class="line-heading-main">
            <div class="line-heading-title">
                <span class="line-heading-number">{{ orderNumber }}</span>
                <span class="line-heading-customer">{{ customerName }}</span>
            </div>
            <div class="line-heading-product">{{ productName }}</div>
        </div>
        <div class="line-heading-side">
            <div class="line-heading-aside">
                <span v-if="lineStatus" class="badge" :class="lineStatus.className">{{ lineStatus.name }}</span>
                <a
                    v-if="canOpenPanel"
                    :href="panelUrl"
                    target="_blank"
                    class="line-heading-link"
                    :title="$t('_go_to_panel')"
                    :aria-label="$t('_go_to_panel')"
                    @click.exact.prevent="openPanel"
                >
                    <font-awesome-icon icon="link" />
                </a>
            </div>
            <div v-if="$slots.aside" class="line-heading-aside-extra">
                <slot name="aside" />
            </div>
        </div>

        <!-- .b-sidebar ma stały transform, więc drawer zagnieżdżony w DOM mierzyłby się względem rodzica, nie okna -->
        <MountingPortal v-if="panelMounted" mount-to="body" append>
            <OrderPanelDrawer
                v-model="panelOpen"
                :line-id="agreementLineId"
                :active-department="panelDepartment"
                @saved="onPanelSaved"
                @closed="onPanelClosed"
            />
        </MountingPortal>
    </div>
</template>

<style scoped lang="scss">
@use './tokens' as *;

.line-heading {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
}

.line-heading-main {
    min-width: 0;
}

.line-heading-title {
    display: flex;
    flex-wrap: wrap;
    align-items: baseline;
    column-gap: 0.6rem;
    line-height: 1.3;
}

.line-heading-number {
    color: $showcase-primary;
    font-size: 1.05rem;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
}

.line-heading-customer {
    color: $showcase-ink-strong;
    font-size: 0.95rem;
    font-weight: 600;
}

.line-heading-product {
    color: $showcase-muted;
    margin-top: 0.15rem;
}

.line-heading-side {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    flex-shrink: 0;
}

.line-heading-aside {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.line-heading-aside-extra {
    margin-top: 0.15rem;
    white-space: nowrap;
}

.line-heading-link {
    color: rgba(var(--colorPrimaryRgb), 0.75);
    border-radius: 4px;
    padding: 0.1rem 0.2rem;

    &:hover {
        color: $showcase-primary;
    }

    &:focus-visible {
        outline: 2px solid rgba(var(--colorPrimaryRgb), 0.5);
        outline-offset: 2px;
    }
}

.line-heading--compact {
    gap: 0.5rem;

    .line-heading-title {
        column-gap: 0.4rem;
    }

    .line-heading-number {
        font-size: 0.95rem;
    }

    .line-heading-customer {
        font-size: 0.85rem;
    }

    .line-heading-product {
        font-size: 0.85rem;
        margin-top: 0.05rem;
    }

    .line-heading-aside {
        gap: 0.5rem;
    }
}
</style>
