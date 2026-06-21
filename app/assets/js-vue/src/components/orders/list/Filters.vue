<template>
    <div class="filter-toolbar d-flex flex-wrap align-items-center">
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
        />
        <date-picker
            width="210px"
            v-model="filtersCollection.dateDelivery"
            :placeholder="$t('deliveryDate')"
        />
    </div>
</template>

<script>

    import DatePicker from '../../base/DatePicker';

    export default {
        name: "filters",
        props: {
            filtersCollection: {
                type: Object,
                default: () => {}
            }
        },

        components: { DatePicker },

        data() {
            return {}
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