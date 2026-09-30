<template>
    <div class="filter-toolbar d-flex align-items-center" :class="stacked ? 'flex-column align-items-stretch' : 'flex-wrap'">
        <div class="filter-toolbar__search input-group">
            <div class="input-group-prepend">
                <span class="input-group-text"><i class="fa fa-search" aria-hidden="true"/></span>
            </div>
            <input
                type="text"
                class="form-control"
                v-model="filtersCollection.q"
                :placeholder="$t('search')"
                :aria-label="$t('search')"
            >
        </div>

        <date-picker
            width="210px"
            v-model="filtersCollection.dateStart"
            :placeholder="$t('receiveDate')"
            :presets="pastPresets"
        />
        <date-picker
            width="210px"
            v-model="filtersCollection.dateDelivery"
            :placeholder="$t('deliveryDate')"
            presets
        />

        <options-multi-select
            v-model="filtersCollection.customers"
            :options="options.customers"
            :placeholder="$t('customer')"
        />

        <options-multi-select
            v-model="filtersCollection.authors"
            :options="options.authors"
            :placeholder="$t('orders.issuedBy')"
        />

        <b-form-checkbox
            v-if="has('hideArchive')"
            class="filter-toolbar__toggle"
            v-model="filtersCollection.hideArchive"
            switch
        >{{ $t('orders.hideArchivedOrder') }}</b-form-checkbox>

        <b-form-checkbox
            v-if="has('overdue')"
            class="filter-toolbar__toggle"
            v-model="filtersCollection.overdue"
            switch
        >{{ $t('orders.onlyOverdue') }}</b-form-checkbox>

        <b-form-checkbox
            v-if="has('notStarted')"
            class="filter-toolbar__toggle"
            v-model="filtersCollection.notStarted"
            switch
        >{{ $t('orders.onlyNotStarted') }}</b-form-checkbox>

        <b-form-checkbox
            v-if="has('startDelayed')"
            class="filter-toolbar__toggle"
            v-model="filtersCollection.startDelayed"
            switch
        >{{ $t('orders.onlyStartedDelay') }}</b-form-checkbox>
    </div>
</template>

<script>

    import DatePicker from '../../base/DatePicker';
    import OptionsMultiSelect from '../../base/OptionsMultiSelect';
    import { PAST_PRESET_KEYS } from '@/services/dateRangePresets';
    import { filterOptions, loadFilterOptions } from '@/services/orderFilterOptions';

    export default {
        name: "filters",
        props: {
            filtersCollection: {
                type: Object,
                default: () => {}
            },

            /** Układ pionowy - do panelu filtrów w dropdownie. */
            stacked: {
                type: Boolean,
                default: false
            }
        },

        components: { DatePicker, OptionsMultiSelect },

        created() {
            loadFilterOptions();
        },

        computed: {
            options() {
                return filterOptions;
            },

            // data otrzymania zawsze jest już za nami - skróty w przyszłość nic by nie zwróciły
            pastPresets() {
                return PAST_PRESET_KEYS;
            }
        },

        methods: {
            has(key) {
                return Boolean(this.filtersCollection) && this.filtersCollection[key] !== undefined;
            }
        },
    }
</script>

<style scoped lang="scss">
    .filter-toolbar {
        gap: 0.6rem 0.85rem;

        // Single consistent control height across search and date pickers.
        .form-control,
        .input-group-text,
        :deep(.mx-input) {
            height: 36px;
        }

        &.flex-column &__search,
        &.flex-column :deep(.mx-datepicker) {
            width: 100%;
            flex: 0 0 auto;
        }

        &__search {
            width: 230px;
            flex: 0 1 230px;

            .input-group-text {
                background-color: #fff;
                border-right: 0;
                color: #b0b6bd;
            }

            .form-control {
                border-left: 0;
                padding-left: 0;
            }
        }

        &__toggle {
            white-space: nowrap;
        }
    }
</style>