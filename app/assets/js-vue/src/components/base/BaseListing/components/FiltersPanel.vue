<script>
import Listing from "../model/Listing"

export default {
    name: "FiltersPanel",

    props: {
        listing: {
            type: Listing,
            required: true
        }
    },

    computed: {
        activeCount() {
            return this.listing.activeCriteria.length;
        },

        activeViewTitle() {
            const view = this.listing.activeView;

            return view ? view.title : '';
        }
    },

    methods: {
        clearAll() {
            this.listing.clearCriteria();
        },
    }
}
</script>

<template>
    <b-nav-item-dropdown right>
        <template #button-content>
            <font-awesome-icon icon="filter" />
            <span class="ml-1">{{ $t('listing.filters') }}</span>
            <b-badge v-if="activeCount" variant="primary" pill class="ml-1">{{ activeCount }}</b-badge>
        </template>

        <b-dropdown-form class="listing-filters">
            <div class="listing-filters__header">
                <span>{{ $t('listing.filters') }}</span>
                <b-button size="sm" variant="link" :disabled="!activeCount" @click="clearAll">
                    {{ $t('listing.clearFilters') }}
                </b-button>
            </div>

            <slot />
        </b-dropdown-form>
    </b-nav-item-dropdown>
</template>

<style scoped lang="scss">

.listing-filters {
    min-width: 320px;

    &__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 0.5rem;
        font-weight: 600;
    }

    &__footer {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.25rem 1rem;
        margin-top: 0.75rem;
        padding-top: 0.75rem;
        border-top: 1px solid #e3e6f0;
    }

    &__state {
        font-size: 0.8rem;
        color: #666;
    }

    &__actions {
        display: flex;
        align-items: center;
        gap: 0.25rem;
        margin-left: auto;
        white-space: nowrap;
    }
}

</style>
