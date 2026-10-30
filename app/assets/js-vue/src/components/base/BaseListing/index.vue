<script>
import Listing from "./model/Listing"
import RenameViewModal from "./components/RenameViewModal"
import CriteriaChips from "./components/CriteriaChips"

// Filters and columns live in ListingToolbar, which the views put in the page header.
export default {
    name: "AgreementLineList",

    components: { RenameViewModal, CriteriaChips },

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
    },

    methods: {
        isActive(view) {
            return Boolean(this.activeView) && this.activeView.id === view.id;
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
                okTitle: this.$t('_delete'),
                cancelVariant: 'outline-secondary',
                cancelTitle: this.$t('_cancel'),
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
            <b-nav tabs class="listing-tabs">
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
                        <b-dropdown-item-btn @click="createView">
                            {{ $t('listing.newView') }}
                        </b-dropdown-item-btn>
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

<style scoped>
.listing-tabs {
    margin-bottom: 0.75rem;
}

.listing-tabs ::v-deep .nav-link.active,
.listing-tabs ::v-deep .nav-link.active:hover,
.listing-tabs ::v-deep .nav-link.active:focus {
    background-color: #fff;
    border: 1px solid var(--colorPrimary);
    border-bottom: 0;
    padding-bottom: calc(0.5rem + 1px);
    color: var(--colorPrimary);
}
</style>
