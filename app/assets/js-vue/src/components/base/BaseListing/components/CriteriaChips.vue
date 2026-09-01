<script>
import Listing from "../model/Listing"

export default {
    name: "CriteriaChips",

    props: {
        listing: {
            type: Listing,
            required: true
        }
    },

    computed: {
        chips() {
            return this.listing.activeCriteria;
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
    <div v-if="hasContent" class="criteria-chips">
        <span class="criteria-chips__label">{{ $t('listing.filters') }}:</span>

        <span v-for="chip in chips" :key="chip.criterion.id" class="criteria-chips__chip">
            {{ chip.criterion.describe(chip.value) }}
            <b-button size="sm" variant="link" class="criteria-chips__remove" @click="clear(chip.criterion.id)">
                <i class="fa fa-times" />
            </b-button>
        </span>

        <span class="ml-auto">
            <b-button v-if="chips.length" size="sm" variant="outline-primary" @click="clearAll">
                {{ $t('listing.clearFilters') }}
            </b-button>

            <template v-if="isDirty">
                <b-button size="sm" variant="outline-primary" @click="save">{{ $t('listing.saveFilters') }}</b-button>
                <b-button size="sm" variant="outline-primary" @click="restore">{{ $t('listing.restoreFilters') }}</b-button>
            </template>
        </span>
    </div>
</template>

<style scoped lang="scss">

.criteria-chips {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.35rem 0.5rem;
    margin: 0.75rem 0;
    font-size: 0.8rem;

    &__label {
        color: #666;
    }

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

    &__remove.btn.btn-link {
        padding: 0 0.35rem !important;
        line-height: 1;
        color: var(--colorWhite);
    }

    &__dirty {
        color: #4E73DF;
    }

    .btn {
        padding: 0 0.25rem;
    }
}

</style>
