<script>
import Listing from "../model/Listing"
import FiltersDropdown from "@/components/base/FiltersDropdown.vue"

export default {
    name: "ListingToolbar",

    components: { FiltersDropdown },

    props: {
        listing: {
            type: Listing,
            required: true
        }
    },

    computed: {
        activeView() {
            return this.listing.activeView;
        },

        /**
         * Najpierw kolumny widoczne, w kolejności widoku, potem reszta do włączenia.
         *
         * @returns {Array<{column: Column, visible: boolean, position: number}>}
         */
        columnEntries() {
            const visible = this.listing.visibleColumns;
            const hidden = this.listing.supportedColumns
                .filter(column => !visible.includes(column));

            return [
                ...visible.map((column, position) => ({ column, visible: true, position })),
                ...hidden.map(column => ({ column, visible: false, position: -1 })),
            ];
        },

        visibleColumnCount() {
            return this.listing.visibleColumns.length;
        }
    },

    methods: {
        toggleColumn(id) {
            this.listing.toggleColumn(id);
        },

        moveColumn(id, offset) {
            this.listing.moveColumn(id, offset);
        },

        isColumnPinned(id) {
            return this.listing.isColumnPinned(id);
        },

        canPinColumn(id) {
            return this.listing.canPinColumn(id);
        },

        togglePin(id) {
            this.listing.togglePinnedColumn(id);
        },

        createView() {
            this.listing.createView(
                this.$t('listing.newViewTitle'),
                this.listing.supportedColumnIds
            )
        },
    }
}
</script>

<template>
    <b-nav class="listing-toolbar">
        <FiltersDropdown
            v-if="$scopedSlots.filters || $slots.filters"
            :count="listing.activeCriteria.length"
            @clear="listing.clearCriteria()"
        >
            <slot name="filters" />
        </FiltersDropdown>

        <b-nav-item-dropdown right>
            <template #button-content>
                <font-awesome-icon icon="cog" />
                <span class="ml-1">{{ $t('listing.columns') }}</span>
            </template>

            <b-dropdown-text>{{ $t('listing.columns') }}</b-dropdown-text>
            <b-dropdown-form v-if="activeView" class="listing-columns">
                <div
                    v-for="entry in columnEntries"
                    :key="entry.column.id"
                    class="listing-columns__row"
                >
                    <b-form-checkbox
                        :checked="entry.visible"
                        @change="toggleColumn(entry.column.id)"
                    >
                        {{ entry.column.label }}
                    </b-form-checkbox>

                    <span class="listing-columns__actions" v-if="entry.visible">
                        <b-button
                            size="sm"
                            variant="link"
                            :class="isColumnPinned(entry.column.id) ? 'text-primary' : 'text-muted'"
                            :disabled="!canPinColumn(entry.column.id)"
                            :title="isColumnPinned(entry.column.id) ? $t('listing.unpinColumn') : $t('listing.pinColumn')"
                            @click="togglePin(entry.column.id)"
                        >
                            <font-awesome-icon icon="thumbtack" />
                        </b-button>
                        <b-button
                            size="sm"
                            variant="link"
                            :disabled="entry.position === 0"
                            :title="$t('listing.moveColumnUp')"
                            @click="moveColumn(entry.column.id, -1)"
                        >
                            <font-awesome-icon icon="chevron-up" />
                        </b-button>
                        <b-button
                            size="sm"
                            variant="link"
                            :disabled="entry.position === visibleColumnCount - 1"
                            :title="$t('listing.moveColumnDown')"
                            @click="moveColumn(entry.column.id, 1)"
                        >
                            <font-awesome-icon icon="chevron-down" />
                        </b-button>
                    </span>
                </div>
            </b-dropdown-form>
            <b-dropdown-divider />
            <b-dropdown-item-btn @click="createView">{{ $t('listing.newView') }}</b-dropdown-item-btn>
        </b-nav-item-dropdown>
    </b-nav>
</template>

<style scoped lang="scss">

.listing-toolbar {
    flex-wrap: nowrap;

    ::v-deep .nav-link {
        padding: 0.25rem 0.75rem;
    }
}

.listing-columns {
    max-height: 60vh;
    overflow-y: auto;

    &__row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        white-space: nowrap;
    }

    &__actions {
        display: flex;

        .btn {
            padding: 0 0.25rem;
            line-height: 1;
        }
    }
}

</style>
