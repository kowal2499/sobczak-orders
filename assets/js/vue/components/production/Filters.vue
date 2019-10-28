<template>
    <div class="card-menu d-flex">

        <div class="form-group">
            <label>{{ $t('search') }}</label><br>
            <input type="text" class="form-control" v-model="model.q" style="height: 34px;">
        </div>

        <div class="form-group">
            <label>{{ $t('receiveDate') }}</label><br>
            <date-picker v-model="model.dateStart"/>
        </div>

        <div class="form-group">
            <label>{{ $t('deliveryDate') }}</label><br>
            <date-picker v-model="model.dateDelivery"/>
        </div>

        <div class="form-group">
            <label>{{ $t('orders.hideArchivedOrder') }}</label><br>
            <input type="checkbox" v-model="model.hideArchive">
        </div>

    </div>
</template>

<script>

    import DatePicker from '../base/datepicker';
    export default {
        name: "Filters",
        components: { DatePicker },

        props: ['model']
    }
</script>

<style scoped lang="scss">

    .form-group + .form-group {
        margin-left: 10px;
    }

</style>