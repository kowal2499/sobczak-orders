<template>

    <div class="card-menu d-flex">

        <div class="form-group">
            <label>Szukaj</label><br>
            <input type="text" class="form-control" v-model="filtersCollection.q" style="height: 34px;">
        </div>

        <div class="form-group">
            <label>Data otrzymania</label><br>
            <date-picker v-model="filtersCollection.dateStart"></date-picker>
        </div>

        <div class="form-group">
            <label>Status</label><br>

            <div class="outline">
                <div class="form-check form-check-inline">
                    <label class="form-check-label">
                        <input class="form-check-input" type="radio" name="inlineRadioOptions" :value="false" v-model="filtersCollection.archived">
                        W realizacji
                    </label>
                </div>
                <div class="form-check form-check-inline">
                    <label class="form-check-label">
                        <input class="form-check-input" type="radio" name="inlineRadioOptions" :value="true" v-model="filtersCollection.archived">
                        W archiwum
                    </label>
                </div>
            </div>
        </div>


        <slot></slot>

    </div>

</template>

<script>

    import DatePicker from '../../base/datepicker';

    export default {
        name: "filters",
        props: ['filtersCollection'],

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