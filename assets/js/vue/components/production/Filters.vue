<template>
    <div class="card-menu d-flex">

        <div class="form-group">
            <label>Szukaj</label><br>
            <input type="text" class="form-control" v-model="model.q" style="height: 34px;">
        </div>

        <div class="form-group">
            <label>Data otrzymania</label><br>
            <date-picker v-model="model.dateStart"/>
        </div>

        <div class="form-group">
            <label>Data dostawy</label><br>
            <date-picker v-model="model.dateDelivery"/>
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