<template>

    <div class="form-row">

        <div class="form-group col-md-3">
            <label>{{ $t('search') }}</label><br>
            <input type="text" class="form-control" v-model="filtersCollection.q" style="height: 34px;">
        </div>

        <div class="form-group">
            <label>{{ $t('receiveDate') }}</label><br>
            <date-picker v-model="filtersCollection.dateStart"></date-picker>
        </div>

        <div class="form-group">
            <label>{{ $t('deliveryDate') }}</label><br>
            <date-picker v-model="filtersCollection.dateDelivery"></date-picker>
        </div>

        <div class="col">
            <slot></slot>
        </div>

    </div>

</template>

<script>

    import DatePicker from '../../base/datepicker';

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
    .form-group + .form-group {
        margin-left: 10px;
    }

    .form-group {
        label {
            color: #4e73df;
        }

        .outline {
            padding: 5px;
            padding-left: 10px;
            border-radius: 3px;
            border: 1px solid #ccc;
            box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.075);
        }
    }
</style>