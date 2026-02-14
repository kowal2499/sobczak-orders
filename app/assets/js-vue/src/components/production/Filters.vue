<template>
    <div class="form-row">
        <div class="form-group col-md-3">
            <label>{{ $t('search') }}</label><br>
            <input type="text" class="form-control" v-model="filtersCollection.q" style="height: 34px;">
        </div>

        <div class="form-group col-md-3">
            <label>{{ $t('receiveDate') }}</label><br>
            <date-picker v-model="filtersCollection.dateStart" style="width: 100%;"/>
        </div>

        <div class="form-group col-md-3" v-if="canShowDeliveryDateFilter">
            <label>{{ $t('deliveryDate') }}</label><br>
            <date-picker v-model="filtersCollection.dateDelivery" style="width: 100%"/>
        </div>

        <div class="form-group col-sm-3">
            <b-form-checkbox v-model="filtersCollection.hideArchive" switch>{{ $t('orders.hideArchivedOrder') }}</b-form-checkbox>
        </div>

        <div class="col">
            <slot />
        </div>
    </div>
</template>

<script>

    import DatePicker from '../base/DatePicker';
    export default {
        name: "Filters",
        components: { DatePicker },

        props: {
            filtersCollection: {
                type: Object,
                default: () => {}
            }
        },

        computed: {
            canShowDeliveryDateFilter() {
                return this.$user && this.$user.can('production.show.production_date');
            }
        }
    }
</script>

<style scoped lang="scss">

    .form-group + .form-group {
        margin-left: 10px;
    }

</style>