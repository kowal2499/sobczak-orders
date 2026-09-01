<script>
import Listing from "./model/Listing"
import RenameViewModal from "./components/RenameViewModal"
import FiltersPanel from "./components/FiltersPanel"
import CriteriaChips from "./components/CriteriaChips"

export default {
    name: "AgreementLineList",

    components: { RenameViewModal, FiltersPanel, CriteriaChips },

    props: {
        listingConfiguration: {
            type: Listing,
            required: true
        }
    },

    computed: {
        viewCollection() {
            return this.listingConfiguration.viewCollection;
        },

        fetchingData() {
            return this.listingConfiguration.fetchingData;
        },

        activeView() {
            return this.listingConfiguration.activeView;
        },

        /**
         * Najpierw kolumny widoczne, w kolejności widoku, potem reszta do włączenia.
         *
         * @returns {Array<{column: Column, visible: boolean, position: number}>}
         */
        columnEntries() {
            const visible = this.listingConfiguration.visibleColumns;
            const hidden = this.listingConfiguration.supportedColumns
                .filter(column => !visible.includes(column));

            return [
                ...visible.map((column, position) => ({ column, visible: true, position })),
                ...hidden.map(column => ({ column, visible: false, position: -1 })),
            ];
        },

        visibleColumnCount() {
            return this.listingConfiguration.visibleColumns.length;
        }
    },

    methods: {
        isActive(view) {
            return Boolean(this.activeView) && this.activeView.id === view.id;
        },

        toggleColumn(id) {
            this.listingConfiguration.toggleColumn(id);
        },

        moveColumn(id, offset) {
            this.listingConfiguration.moveColumn(id, offset);
        },

        isColumnPinned(id) {
            return this.listingConfiguration.isColumnPinned(id);
        },

        canPinColumn(id) {
            return this.listingConfiguration.canPinColumn(id);
        },

        togglePin(id) {
            this.listingConfiguration.togglePinnedColumn(id);
        },

        createView() {
            this.listingConfiguration.createView(
                this.$t('listing.newViewTitle'),
                this.listingConfiguration.supportedColumnIds
            )
        },

        onViewChange(id) {
            this.listingConfiguration.setActiveView(id)
        },

        moveView(id, offset) {
            this.listingConfiguration.moveView(id, offset);
        },

        onRenamed(title) {
            this.listingConfiguration.renameView(this.activeView.id, title);
        },

        confirmRemove(view) {
            this.$bvModal.msgBoxConfirm(this.$t('listing.removeViewConfirm', { title: view.title }), {
                title: this.$t('listing.removeView'),
                okVariant: 'danger',
                okTitle: this.$t('_yes'),
                cancelTitle: this.$t('_no'),
                centered: true,
            }).then(confirmed => {
                if (confirmed) {
                    this.listingConfiguration.removeView(view.id);
                }
            });
        }
    },

    data() {
        return {
            renaming: false
        }
    }
}
</script>

<template>
    <div>
        <div v-if="fetchingData">{{ $t('listing.loading') }}</div>
        <template v-else>
            <b-nav tabs>
                <template v-for="(view, index) in viewCollection">
                    <b-nav-item-dropdown
                        v-if="isActive(view)"
                        :key="view.id"
                        :text="view.title"
                        toggle-class="active"
                    >
                        <b-dropdown-item-btn @click="renaming = true">
                            {{ $t('listing.renameView') }}
                        </b-dropdown-item-btn>
                        <b-dropdown-item-btn
                            :disabled="index === 0"
                            @click="moveView(view.id, -1)"
                        >
                            {{ $t('listing.moveViewLeft') }}
                        </b-dropdown-item-btn>
                        <b-dropdown-item-btn
                            :disabled="index === viewCollection.length - 1"
                            @click="moveView(view.id, 1)"
                        >
                            {{ $t('listing.moveViewRight') }}
                        </b-dropdown-item-btn>
                        <b-dropdown-divider />
                        <b-dropdown-item-btn
                            variant="danger"
                            :disabled="viewCollection.length <= 1"
                            @click="confirmRemove(view)"
                        >
                            {{ $t('listing.removeView') }}
                        </b-dropdown-item-btn>
                    </b-nav-item-dropdown>

                    <b-nav-item v-else :key="view.id" @click="onViewChange(view.id)">
                        {{ view.title }}
                    </b-nav-item>
                </template>

                <FiltersPanel v-if="$scopedSlots.filters" :listing="listingConfiguration" class="ml-auto">
                    <slot name="filters" />
                </FiltersPanel>

                <b-nav-item-dropdown :class="$scopedSlots.filters ? '' : 'ml-auto'" right>
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

            <CriteriaChips :listing="listingConfiguration" />

            <slot :view="activeView" />

            <RenameViewModal
                v-if="activeView"
                v-model="renaming"
                :title="activeView.title"
                @renamed="onRenamed"
            />
        </template>
    </div>
</template>

<style scoped lang="scss">

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
