<template>
    <div class="card">
        <div
            class="card-body"
            :class="[
                pinnedTitle ? 'card-body--pinned' : 'card-body--scroll',
                isBusy && !isLoading && 'card-body--busy',
                isLoading && 'card-body--loading',
            ]"
        >
            <div class="d-flex justify-content-between">
                <div class="text-title font-weight-bold text-primary text-uppercase mb-1">
                    <slot name="title" />
                </div>
                <div class="text-right">
                    <font-awesome-icon v-if="isBusy && !isLoading" icon="spinner" spin />
                    <span v-show="!isBusy && $slots.description" :id="popoverTargetId">
                        <font-awesome-icon icon="info-circle" class="text-primary" />
                    </span>
                </div>
            </div>

            <div v-if="isLoading" class="metric-loading" aria-hidden="true">
                <font-awesome-icon icon="spinner" spin />
            </div>
            <template v-else>
                <div v-if="pinnedTitle" class="card-body__content">
                    <slot />
                </div>
                <div v-else class="h5 mb-0 font-weight-bold text-gray-800">
                    <slot />
                </div>

                <div v-if="trend && trend.pending" class="card-body__trend card-body__trend--pending" />
                <MetricTrend v-else-if="trend" :trend="trend" class="card-body__trend" />
            </template>

            <!-- domyślna granica to scrollParent, czyli przewijana karta - wysoki opis wystawał wtedy poza okno -->
            <b-popover :target=popoverTargetId triggers="hover" boundary="viewport">
                <template #title><span class="text-primary"><slot name="title" /></span></template>
                <slot name="description" />
            </b-popover>
        </div>
    </div>
</template>

<script>
import MetricTrend from "./MetricTrend.vue"

export default {
    name: "BaseBadge",
    components: { MetricTrend },
    props: {
        // wykresik dynamiki z historii mierników (services/trends.js); null = kafel bez wykresu
        trend: {
            type: Object,
            default: null
        },
        isBusy: {
            type: Boolean,
            default: false
        },
        // pierwsze ładowanie (brak danych): kręciołek zamiast treści; odświeżenie istniejących danych tylko je przygasza
        isLoading: {
            type: Boolean,
            default: false
        },
        // długie listy: przewija się tylko treść, tytuł zostaje na miejscu
        pinnedTitle: {
            type: Boolean,
            default: false
        },
    },
    computed: {
        popoverTargetId() {
            return `popover-target-${this._uid}`
        }
    }
}
</script>

<style scoped lang="scss">
:deep(strong) {
    color: var(--colorPrimary)
}

// Inside the dashboard grid the card must fill its cell, not impose its own
// intrinsic height/margins (leftovers from the old bootstrap-row layout) -
// otherwise it overflows the grid item and overlaps neighbouring widgets.
.card {
    width: 100%;
    height: 100%;
    margin: 0;
}

.card-body--loading {
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.metric-loading {
    flex: 1 1 auto;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 0;
    font-size: 2.5rem;
    color: var(--colorSecondary);
    opacity: .6;
}

.card-body--scroll {
    overflow-y: auto;
}

.card-body--pinned {
    display: flex;
    flex-direction: column;
    min-height: 0;
    overflow: hidden;
}

.card-body__trend {
    flex: 0 0 auto;
    margin-top: .5rem;

    // miejsce na wykres, który jeszcze się wczytuje
    &--pending {
        height: 36px;
    }
}

.card-body__content {
    flex: 1 1 auto;
    min-height: 0;
    overflow-y: auto;
}

// Przygaszenie na czas ładowania obejmuje całe poddrzewo karty - razem z wysuniętym z niej
// drawerem (slot #default), przez który prześwitywałby pulpit. Gdy jakikolwiek drawer jest
// otwarty (klasa ustawiana przez components/base/Sidebar.vue), zostaje sam spinner w nagłówku.
body:not(.sidebar-open) .card-body--busy {
    opacity: 0.5;
}

.border-left-success {
    border-left: .25rem solid #1cc88a!important;
}

.border-left-info {
    border-left: .25rem solid #36b9cc!important;
}

.border-left-warning {
    border-left: .25rem solid #f6c23e!important;
}

.border-left-primary {
    border-left: .25rem solid #4e73df!important;
}

.text-title {
    font-size: .7rem;
}
</style>