<script>
import Listing from "../model/Listing"
import FilterChips from "@/components/base/FilterChips.vue"

export default {
    name: "CriteriaChips",

    components: { FilterChips },

    props: {
        listing: {
            type: Listing,
            required: true
        }
    },

    computed: {
        chips() {
            return this.listing.activeCriteria.map(chip => ({
                id: chip.criterion.id,
                label: chip.criterion.describe(chip.value),
            }));
        },

        isDirty() {
            return this.listing.isCriteriaDirty;
        },

        hasContent() {
            return this.chips.length > 0 || this.isDirty;
        }
    },

    methods: {
        clear(id) {
            this.listing.clearCriterion(id);
        },

        clearAll() {
            this.listing.clearCriteria();
        },

        save() {
            this.listing.saveCriteria();
        },

        restore() {
            this.listing.restoreCriteria();
        }
    }
}
</script>

<template>
    <FilterChips v-if="hasContent" :chips="chips" @remove="clear" @clear="clearAll">
        <template v-if="isDirty">
            <b-button size="sm" variant="outline-primary" @click="save">{{ $t('listing.saveFilters') }}</b-button>
            <b-button size="sm" variant="outline-primary" @click="restore">{{ $t('listing.restoreFilters') }}</b-button>
        </template>
    </FilterChips>
</template>
