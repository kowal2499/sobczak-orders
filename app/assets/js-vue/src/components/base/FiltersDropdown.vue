<script>
export default {
    name: "FiltersDropdown",

    props: {
        count: {
            type: Number,
            default: 0
        }
    },

    emits: ['clear']
}
</script>

<template>
    <b-nav-item-dropdown right>
        <template #button-content>
            <font-awesome-icon icon="filter" />
            <span class="ml-1">{{ $t('listing.filters') }}</span>
            <b-badge v-if="count" variant="primary" pill class="ml-1 filters-count">{{ count }}</b-badge>
        </template>

        <b-dropdown-form class="filters-dropdown">
            <div class="filters-dropdown__header">
                <span>{{ $t('listing.filters') }}</span>
                <b-button size="sm" variant="link" :disabled="!count" @click="$emit('clear')">
                    {{ $t('listing.clearFilters') }}
                </b-button>
            </div>

            <slot />
        </b-dropdown-form>
    </b-nav-item-dropdown>
</template>

<style scoped lang="scss">

.filters-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 1.15rem;
    height: 1.15rem;
    padding: 0 0.35rem;
    font-size: 0.7rem;
    line-height: 1;
    vertical-align: middle;
}

.filters-dropdown {
    min-width: 320px;

    &__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 0.5rem;
        font-weight: 600;
    }
}

</style>
