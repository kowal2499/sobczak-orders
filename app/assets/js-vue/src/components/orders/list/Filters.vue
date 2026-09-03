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
    </div>
</template>

<script>

    import DatePicker from '../../base/DatePicker';
    import { PAST_PRESET_KEYS } from '@/services/dateRangePresets';

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

        components: { DatePicker },

        computed: {
            // data otrzymania zawsze jest już za nami - skróty w przyszłość nic by nie zwróciły
            pastPresets() {
                return PAST_PRESET_KEYS;
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
    }
</style>