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

        <div class="form-group col-md-3">
            <label>{{ $t('deliveryDate') }}</label><br>
            <date-picker v-model="filtersCollection.dateDelivery" style="width: 100%"/>
        </div>

        <div class="form-group col-sm-3">
            <label>{{ $t('orders.hideArchivedOrder') }}</label><br>
            <input type="checkbox" v-model="filtersCollection.hideArchive">
        </div>

        <div class="col">
            <slot></slot>
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
    }
</script>

<style scoped lang="scss">

    .form-group + .form-group {
        margin-left: 10px;
    }

</style>