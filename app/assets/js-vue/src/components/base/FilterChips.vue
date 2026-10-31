<script>
export default {
    name: "FilterChips",

    props: {
        // [{ id, label }]
        chips: {
            type: Array,
            default: () => []
        }
    },

    emits: ['remove', 'clear']
}
</script>

<template>
    <div v-if="chips.length || $slots.default" class="filter-chips">
        <span v-for="chip in chips" :key="chip.id" class="filter-chips__chip">
            {{ chip.label }}
            <b-button size="sm" variant="link" class="filter-chips__remove" @click="$emit('remove', chip.id)">
                <i class="fa fa-times" />
            </b-button>
        </span>

        <span class="filter-chips__actions">
            <b-button v-if="chips.length" size="sm" variant="outline-secondary" @click="$emit('clear')">
                {{ $t('listing.clearFilters') }}
            </b-button>
            <slot />
        </span>
    </div>
</template>

<style scoped lang="scss">

.filter-chips {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.35rem 0.5rem;
    margin-bottom: 0.75rem;
    font-size: 0.8rem;

    &__chip {
        display: inline-flex;
        align-items: center;
        padding: 0.1rem 0.2rem 0.1rem 0.6rem;
        border: 1px solid #e3e6f0;
        border-radius: 1rem;
        background-color: rgba(var(--colorPrimaryRgb), 0.8);
        color: var(--colorWhite);
        white-space: nowrap;
    }

    &__actions {
        display: inline-flex;
        gap: 0.35rem;
        margin-left: auto;
    }

    &__remove.btn.btn-link {
        padding: 0 0.35rem !important;
        line-height: 1;
        color: var(--colorWhite);
    }

    .btn {
        padding: 0 0.25rem;
    }
}

</style>
